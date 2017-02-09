<?php
namespace Wwwision\Neos\MailChimp\Domain\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Http\Client\RequestEngineInterface;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\QueryResultInterface;
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
    protected $requestEngine;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiEndpoint;

    /**
     * @Flow\Inject(lazy=false)
     * @var VariableFrontend
     */
    protected $cache;

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
     * @param string $interestCategoryId
     * @return array
     */
    public function getCategoryByListIdAndInterestCategoryId($listId, $interestCategoryId)
    {
        $cacheKey = "MailChimp_List_" . $listId ."_Category_" . $interestCategoryId;
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $categoryResult = $this->get("lists/$listId/interest-categories/$interestCategoryId");

        $this->cache->set($cacheKey, $categoryResult, [], 60 * 60 * 1); // 1 hour caching

        return $categoryResult;
    }

    /**
     * @param string $listId
     * @param string $interestCategoryId
     * @return array
     */
    public function getInterestsByListIdAndInterestCategoryId($listId, $interestCategoryId)
    {
        $cacheKey = "MailChimp_List_" . $listId ."_Interests_" . $interestCategoryId;
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $interestsResult = $this->get("lists/$listId/interest-categories/$interestCategoryId/interests");

        $this->cache->set($cacheKey, $interestsResult, [], 60 * 60 * 1); // 1 hour caching

        return $interestsResult;
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
     * @param array $interests
     * @param array $additionalFields
     * @return void
     */
    public function subscribe($listId, $emailAddress, $interests = null, $additionalFields = null)
    {
        $subscriberHash = md5(strtolower($emailAddress));
        $arguments = [
            'email_address' => $emailAddress,
            'status' => 'pending',
        ];
        if ($interests !== null) {
            $arguments['interests'] = $interests;
        }
        if ($additionalFields) {
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
     * @param string $listId
     * @param string $emailAddress
     * @param array $interests
     * @return void
     */
    public function updateInterests($listId, $emailAddress, $interests)
    {
        $subscriberHash = md5(strtolower($emailAddress));
        $this->patch("lists/$listId/members/$subscriberHash", ['email_address' => $emailAddress, 'interests' => $interests]);
    }

    /**
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array
     */
    protected function get($resource, array $arguments = null)
    {
        return $this->makeRequest('GET', $resource, $arguments);
    }

    /**
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array
     */
    protected function put($resource, array $arguments = null)
    {
        return $this->makeRequest('PUT', $resource, $arguments);
    }

    /**
     * @param string $resource The REST resource name (e.g. "lists")
     * @param array|null $arguments Arguments to be send to the API endpoint
     * @return array
     */
    protected function patch($resource, array $arguments = null)
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
    protected function makeRequest($method, $resource, array $arguments = null)
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

    public function getInterestsFormOptionsByListIdAndInterestCategoryId($listId, $categoryId)
    {
        $interestsResult = $this->getInterestsByListIdAndInterestCategoryId($listId, $categoryId);
        $interests = $interestsResult['interests'];
        $options = [];

        usort($interests, function($a, $b) {
            return $a["display_order"] - $b["display_order"];
        });

        foreach ($interests as $interest) {
            $options[$interest['id']] = $interest['name'];
        }


        return $options;
    }

}