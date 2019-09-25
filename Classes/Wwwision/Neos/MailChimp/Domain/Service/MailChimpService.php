<?php
declare(strict_types=1);

namespace Wwwision\Neos\MailChimp\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\RequestEngineInterface;
use Neos\Flow\Http\ContentStream;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Persistence\QueryResultInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
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
     * @Flow\Inject
     * @var UriFactoryInterface
     */
    protected $uriFactory;

    /**
     * @Flow\Inject
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    /**
     * @param string $apiKey MailChimp API key
     * @param RequestEngineInterface $requestEngine
     * @throws InvalidApiKeyException
     */
    public function __construct(string $apiKey, RequestEngineInterface $requestEngine)
    {
        $this->apiKey = $apiKey;
        $this->requestEngine = $requestEngine;

        if (strpos($this->apiKey, '-') === false) {
            throw new InvalidApiKeyException(sprintf('Invalid MailChimp API key %s supplied.', $apiKey), 1483531773);
        }
        [, $dataCenter] = explode('-', $this->apiKey);
        $this->apiEndpoint = sprintf('https://%s.api.mailchimp.com/3.0', $dataCenter);
    }

    /**
     * @return CallbackQueryResult|QueryResultInterface
     */
    public function getLists(): CallbackQueryResult
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
     * @throws HttpException | MailChimpException | ResourceNotFoundException
     */
    public function getListById(string $listId): array
    {
        return $this->get("lists/$listId");
    }

    /**
     * @param string $listId
     * @return CallbackQueryResult|QueryResultInterface
     */
    public function getMembersByListId(string $listId)
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
     * @throws HttpException | MailChimpException
     */
    public function isMember(string $listId, string $emailAddress): ?bool
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
     * @throws HttpException | MailChimpException | ResourceNotFoundException
     */
    public function getMemberInfo(string $listId, string $emailAddress): array
    {
        $subscriberHash = md5(strtolower($emailAddress));
        return $this->get("lists/$listId/members/$subscriberHash");
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @param array $additionalFields
     * @param array $marketingPermissions
     * @return void
     * @throws HttpException | MailChimpException | ResourceNotFoundException
     */
    public function subscribe(string $listId, string $emailAddress, array $additionalFields = null, array $marketingPermissions = null): void
    {
        $subscriberHash = md5(strtolower($emailAddress));
        $arguments = [
            'email_address' => $emailAddress,
            'status' => 'pending',
        ];
        if ($additionalFields !== null) {
            $arguments['merge_fields'] = $additionalFields;
        }
        if ($marketingPermissions !== null) {
            $arguments['marketing_permissions'] = $marketingPermissions;
        }
        $this->put("lists/$listId/members/$subscriberHash", $arguments);
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @return void
     * @throws HttpException | MailChimpException | ResourceNotFoundException
     */
    public function unsubscribe(string $listId, string $emailAddress): void
    {
        $subscriberHash = md5(strtolower($emailAddress));
        $this->put("lists/$listId/members/$subscriberHash", ['email_address' => $emailAddress, 'status' => 'unsubscribed']);
    }

    /**
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array
     * @throws HttpException | MailChimpException | ResourceNotFoundException
     */
    private function get(string $resource, array $arguments = null): array
    {
        return $this->makeRequest('GET', $resource, $arguments);
    }

    /**
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array
     * @throws HttpException | MailChimpException | ResourceNotFoundException
     */
    private function put(string $resource, array $arguments = null): array
    {
        return $this->makeRequest('PUT', $resource, $arguments);
    }

    /**
     * @param string $method The HTTP method
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array The decoded response
     * @throws ResourceNotFoundException | MailChimpException | HttpException
     */
    private function makeRequest(string $method, string $resource, array $arguments = null): array
    {
        $uri = $this->uriFactory->createUri($this->apiEndpoint . '/' . $resource);
        if ($method === 'GET' && $arguments !== null) {
            $uri = $uri->withQuery(http_build_query($arguments));
        }
        $request = $this->serverRequestFactory->createServerRequest($method, $uri)
            ->withHeader('Accept', 'application/vnd.api+json')
            ->withHeader('Content-Type', 'application/vnd.api+json')
            ->withHeader('Authorization', 'apikey ' . $this->apiKey);
        if ($method !== 'GET' && $arguments !== null) {
            $request = $request->withBody(ContentStream::fromContents(json_encode($arguments)));
        }

        $response = $this->requestEngine->sendRequest($request);
        $decodedBody = json_decode($response->getBody()->getContents(), true);
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return $decodedBody;
        }
        $errorMessage = $decodedBody['detail'] ?? 'Unknown error';
        if ($response->getStatusCode() === 404) {
            throw new ResourceNotFoundException($errorMessage, 1483538558);
        }
        throw new MailChimpException($errorMessage, 1483533997);
    }

}
