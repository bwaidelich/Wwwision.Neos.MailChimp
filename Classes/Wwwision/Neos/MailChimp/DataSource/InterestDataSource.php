<?php

namespace Wwwision\Neos\MailChimp\DataSource;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;
use Wwwision\Neos\MailChimp\Exception\MailChimpException;

class InterestDataSource extends AbstractDataSource
{
    /**
     * @var string
     */
    static protected $identifier = 'mailchimp-interest';

    /**
     * @var MailChimpService
     */
    protected $mailChimpService;

    public function __construct(MailChimpService $mailChimpService)
    {
        $this->mailChimpService = $mailChimpService;
    }

    /**
     * @inheritDoc
     */
    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $listId = $arguments['listId'];
        $categoryId = $arguments['categoryId'];

        $response = $this->mailChimpService->getInterestByListAndCategory($listId, $categoryId);
        $interests = $response['interests'] ?? [];

        $data = [];
        foreach ($interests as $interest) {
            $data[] = [
                'value' => $interest['id'],
                'label' => $interest['name']
            ];
        }
        return $data;
    }
}