<?php

namespace Wwwision\Neos\MailChimp\DataSource;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;

class InterestCategoryDataSource extends AbstractDataSource
{
    /**
     * @var string
     */
    static protected $identifier = 'mailchimp-interest-category';

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

        $response = $this->mailChimpService->getInterestCategoriesByList($listId);
        $interestCategories = $response['categories'] ?? [];

        $data = [];
        foreach ($interestCategories as $interestCategory) {
            $data[] = [
                'value' => $interestCategory['id'],
                'label' => $interestCategory['title']
            ];
        }
        return $data;
    }
}