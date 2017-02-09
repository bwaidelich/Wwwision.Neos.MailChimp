<?php

namespace Wwwision\Neos\MailChimp\Form\FormElements;

use TYPO3\Flow\Exception;
use TYPO3\Form\FormElements\GenericFormElement;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;
use TYPO3\Flow\Annotations as Flow;

class MailChimpInterestsFormElement extends GenericFormElement {

    /**
     * @Flow\Inject
     * @var MailChimpService
     */
    protected $mailChimpService;

    /**
     * @param \TYPO3\Form\Core\Runtime\FormRuntime $formRuntime
     *
     * @throws \TYPO3\Flow\Exception
     */
    public function beforeRendering(\TYPO3\Form\Core\Runtime\FormRuntime $formRuntime)
    {
        $properties = $this->getProperties();

        if (!isset($properties['listId'])) {
            throw new Exception('Property "listId" missing', 1486627024);
        }
        if (!isset($properties['categoryId'])) {
            throw new Exception('Property "categoryId" missing', 1486631201);
        }
        $listId = $properties['listId'];
        $categoryId = $properties['categoryId'];

        $categoryResult = $this->mailChimpService->getCategoryByListIdAndInterestCategoryId($listId, $categoryId);

        if (!$this->getLabel()) {
            $this->setLabel($categoryResult['title']);
        }

        $this->setProperty('type', $categoryResult['type']);
        $this->setProperty('options', $this->mailChimpService->getInterestsFormOptionsByListIdAndInterestCategoryId($listId, $categoryId));
    }
}