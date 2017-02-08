<?php
namespace Wwwision\Neos\MailChimp\Form\Finishers;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Form\Core\Model\AbstractFinisher;
use TYPO3\Form\Exception\FinisherException;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;
use Wwwision\Neos\MailChimp\Exception\MailChimpException;

/**
 * A finisher for the TYPO3 Form project allowing for subscribing newsletter recipients
 */
class MailChimpSubscriptionFinisher extends AbstractFinisher
{

    /**
     * @Flow\Inject
     * @var MailChimpService
     */
    protected $mailChimpService;

    /**
     * @var array
     */
    protected $defaultOptions = [
        'listId' => '',
        'emailAddress' => '{email}',
        'additionalFields' => null,
        'interests' => []
    ];

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @return void
     * @throws FinisherException
     */
    protected function executeInternal()
    {
        $listId = $this->parseOption('listId');
        $emailAddress = $this->parseOption('emailAddress');
        $interests = $this->parseOption('interests');

        $additionalFields = $this->replacePlaceholders($this->parseOption('additionalFields'));
        try {
            $this->mailChimpService->subscribe($listId, $emailAddress, $interests, $additionalFields);
        } catch (MailChimpException $exception) {
            throw new FinisherException(sprintf('Failed to subscribe "%s" to list "%s"!', $emailAddress, $listId), 1418060900, $exception);
        }
    }

    /**
     * Recursively replaces "{<var>}" with variables from the form runtime
     *
     * @param array|mixed $field
     * @return array|mixed
     */
    protected function replacePlaceholders($field)
    {
        if (is_array($field)) {
            return array_map([$this, 'replacePlaceholders'], $field);
        }
        return preg_replace_callback('/{([^}]+)}/', function ($match) {
            return ObjectAccess::getPropertyPath($this->finisherContext->getFormRuntime(), $match[1]);
        }, $field);
    }
}