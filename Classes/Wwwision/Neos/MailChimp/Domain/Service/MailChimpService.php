<?php
namespace Wwwision\Neos\MailChimp\Domain\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use Wwwision\Neos\MailChimp\Domain\Dto\CallbackQuery;
use Wwwision\Neos\MailChimp\Domain\Dto\CallbackQueryResult;

/**
 * Central authority to be used when interacting with the MailChimp API
 *
 * @Flow\Scope("singleton")
 */
class MailChimpService
{

    /**
     * @var \Mailchimp
     */
    protected $client;

    /**
     * @param string $apiKey MailChimp API key
     */
    public function __construct($apiKey)
    {
        $this->mailChimpClient = new \Mailchimp($apiKey);
    }

    /**
     * @return CallbackQueryResult
     */
    public function getLists()
    {
        $query = new CallbackQuery(function () {
            $lists = $this->mailChimpClient->lists->getList();
            return $lists['data'];
        });
        return $query->execute();
    }

    /**
     * @param string $listId
     * @return array
     */
    public function getListById($listId)
    {
        $lists = $this->mailChimpClient->lists->getList(array('list_id' => $listId));
        return $lists['data'][0];
    }

    /**
     * @param string $listId
     * @param string $sortField
     * @param string $sortOrder
     * @return CallbackQueryResult
     */
    public function getMembersByListId($listId, $sortField = 'optin_time', $sortOrder = QueryInterface::ORDER_DESCENDING)
    {
        $memberQuery = new CallbackQuery(function (CallbackQuery $query) use ($listId, $sortField, $sortOrder) {
            $limit = $query->getLimit();
            $startPage = (floor($query->getOffset() / $limit));
            $members = $this->mailChimpClient->lists->members($listId, 'subscribed', array('start' => $startPage, 'limit' => $limit, 'sort_field' => $sortField, 'sort_dir' => $sortOrder));
            return $members['data'];
        }, function () use ($listId) {
            $members = $this->mailChimpClient->lists->members($listId, 'subscribed', array('limit' => 0));
            return (integer)$members['total'];
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
            $members = $this->getMemberInfo($listId, $emailAddress);
            return $members['success_count'] > 0;
        } catch (\Exception $exception) {
            return FALSE;
        }
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @return array
     */
    public function getMemberInfo($listId, $emailAddress)
    {
        return $this->mailChimpClient->lists->memberInfo($listId, array(array('email' => $emailAddress)));
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @return void
     */
    public function subscribe($listId, $emailAddress)
    {
        /** @noinspection PhpParamsInspection */
        $this->mailChimpClient->lists->subscribe($listId, ['email' => $emailAddress]);
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @return void
     */
    public function unsubscribe($listId, $emailAddress)
    {
        /** @noinspection PhpParamsInspection */
        $this->mailChimpClient->lists->unsubscribe($listId, ['email' => $emailAddress]);
    }

}
