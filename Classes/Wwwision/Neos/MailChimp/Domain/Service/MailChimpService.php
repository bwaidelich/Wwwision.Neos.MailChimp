<?php
namespace Wwwision\Neos\MailChimp\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\RequestEngineInterface;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Wwwision\Neos\MailChimp\Domain\Dto\CallbackQuery;
use Wwwision\Neos\MailChimp\Domain\Dto\CallbackQueryResult;
use Wwwision\Neos\MailChimp\Exception\InvalidApiKeyException;
use Wwwision\Neos\MailChimp\Exception\MailChimpException;
use Wwwision\Neos\MailChimp\Exception\ResourceNotFoundException;

/**
 * Central authority to be used when interacting with the MailChimp API
 *
 * @Flow\Scope("singleton")
 */
class MailChimpService
{

    /**
     * @var RequestEngineInterface
     */
    private $requestEngine;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiEndpoint;

    /**
     * @param string $apiKey MailChimp API key
     * @param RequestEngineInterface $requestEngine
     * @throws InvalidApiKeyException
     */
    public function __construct($apiKey, RequestEngineInterface $requestEngine)
    {
        $this->apiKey = $apiKey;
        $this->requestEngine = $requestEngine;

        if (strpos($this->apiKey, '-') === false) {
            throw new InvalidApiKeyException(sprintf('Invalid MailChimp API key %s supplied.', $apiKey), 1483531773);
        }
        list(, $dataCenter) = explode('-', $this->apiKey);
        $this->apiEndpoint  = sprintf('https://%s.api.mailchimp.com/3.0', $dataCenter);
    }

    /**
     * @return CallbackQueryResult|QueryResultInterface
     */
    public function getLists()
    {
        $query = new CallbackQuery(function () {
            $lists = $this->get('lists');
            return $lists['lists'];
        });
        return $query->execute();
    }

    /**
     * @param string $listId
     * @return array
     */
    public function getListById($listId)
    {
        return $this->get("lists/$listId");
    }

    /**
     * @param string $listId
     * @return CallbackQueryResult|QueryResultInterface
     */
    public function getMembersByListId($listId)
    {
        $memberQuery = new CallbackQuery(function (CallbackQuery $query) use ($listId) {
            $members = $this->get("lists/$listId/members", ['offset' => $query->getOffset(), 'count' => $query->getLimit()]);
            return $members['members'];
        }, function () use ($listId) {
            $members = $this->get("lists/$listId/members", ['count' => 0]);
            return (integer)$members['total_items'];
        });
        return $memberQuery->execute();
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @return boolean
     */
    public function isMember($listId, $emailAddress)
    {
        try {
            $member = $this->getMemberInfo($listId, $emailAddress);
            return isset($member['status']) && $member['status'] === 'subscribed';
        } catch (ResourceNotFoundException $exception) {
            return false;
        }
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @return array
     */
    public function getMemberInfo($listId, $emailAddress)
    {
        $subscriberHash = md5(strtolower($emailAddress));
        return $this->get("lists/$listId/members/$subscriberHash");
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @param array $additionalFields
     * @return void
     */
    public function subscribe($listId, $emailAddress, array $additionalFields = null)
    {
        $subscriberHash = md5(strtolower($emailAddress));
        $arguments = [
            'email_address' => $emailAddress,
            'status' => 'pending',
        ];
        if ($additionalFields !== null) {
            $arguments['merge_fields'] = $additionalFields;
        }
        $this->put("lists/$listId/members/$subscriberHash", $arguments);
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @return void
     */
    public function unsubscribe($listId, $emailAddress)
    {
        $subscriberHash = md5(strtolower($emailAddress));
        $this->patch("lists/$listId/members/$subscriberHash", ['email_address' => $emailAddress, 'status' => 'unsubscribed']);
    }

    /**
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array
     */
    private function get($resource, array $arguments = null)
    {
        return $this->makeRequest('GET', $resource, $arguments);
    }

    /**
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array
     */
    private function put($resource, array $arguments = null)
    {
        return $this->makeRequest('PUT', $resource, $arguments);
    }

    /**
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array
     */
    private function patch($resource, array $arguments = null)
    {
        return $this->makeRequest('PUT', $resource, $arguments);
    }

    /**
     * @param string $method The HTTP method
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array The decoded response
     * @throws MailChimpException
     */
    private function makeRequest($method, $resource, array $arguments = null)
    {
        $uri = new Uri($this->apiEndpoint . '/' . $resource);
        if ($method === 'GET' && $arguments !== null) {
            $uri->setQuery(http_build_query($arguments));
        }
        $request = Request::create($uri, $method);
        $request->setHeader('Accept', 'application/vnd.api+json');
        $request->setHeader('Content-Type', 'application/vnd.api+json');
        $request->setHeader('Authorization', 'apikey ' . $this->apiKey);
        if ($method !== 'GET' && $arguments !== null) {
            $request->setContent(json_encode($arguments));
        }

        $response = $this->requestEngine->sendRequest($request);
        $decodedBody = json_decode($response->getContent(), true);
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return $decodedBody;
        }
        $errorMessage = isset($decodedBody['detail']) ? $decodedBody['detail'] : 'Unknown error';
        if ($response->getStatusCode() === 404) {
            throw new ResourceNotFoundException($errorMessage, 1483538558);
        }
        throw new MailChimpException($errorMessage, 1483533997);
    }

}