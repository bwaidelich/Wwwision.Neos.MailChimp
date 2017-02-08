<?php

namespace Wwwision\Neos\MailChimp\Eel;

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;


class MailChimpHelper implements ProtectedContextAwareInterface
{

    /**
     * @Flow\Inject
     * @var MailChimpService
     */
    protected $mailChimpService;

    /**
     * @Flow\Inject(lazy=false)
     * @var VariableFrontend
     */
    protected $cache;

    public function getInterestsByListIdAndInterestCategoryId($listId, $categoryId)
    {
        $cacheKey = "MailChimp_Interests_$categoryId";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $interests = $this->mailChimpService->getInterestsByListIdAndInterestCategoryId($listId, $categoryId);
        $this->cache->set($cacheKey, $interests, [], 60 * 60 * 1); // 1 hour caching

        return $interests;
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}