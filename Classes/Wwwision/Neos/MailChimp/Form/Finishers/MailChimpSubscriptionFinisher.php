<?php
namespace Wwwision\Neos\MailChimp\Form\Finishers;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Form\Core\Model\AbstractFinisher;
use TYPO3\Form\Exception\FinisherException;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;

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
        'additionalFields' => []
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

        $additionalFields = array_map(function($field) {
            return preg_replace_callback('/{([^}]+)}/', function ($match) {
                return ObjectAccess::getPropertyPath($this->finisherContext->getFormRuntime(), $match[1]);
            }, $field);
        }, $this->parseOption('additionalFields'));

        try {
            $this->mailChimpService->subscribe($listId, $emailAddress, $additionalFields);
        } catch (\Mailchimp_Error $exception) {
            throw new FinisherException(sprintf('Failed to subscribe "%s" to list "%s"!', $emailAddress, $listId), 1418060900, $exception);
        }
    }
}
