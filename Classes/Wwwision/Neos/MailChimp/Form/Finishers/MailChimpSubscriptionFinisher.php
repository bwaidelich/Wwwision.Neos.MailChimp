<?php
declare(strict_types=1);

namespace Wwwision\Neos\MailChimp\Form\Finishers;

use Neos\Flow\Annotations as Flow;
use Neos\Form\Core\Model\AbstractFinisher;
use Neos\Form\Exception\FinisherException;
use Neos\Utility\ObjectAccess;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;
use Wwwision\Neos\MailChimp\Exception\MailChimpException;

/**
 * A finisher for the Neos Form framework allowing for subscribing newsletter recipients
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
        'interestGroups' => null,
    ];

    /**
     * Executes this finisher
     *
     * @return void
     * @throws FinisherException
     * @throws \Neos\Flow\Http\Exception
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        $listId = $this->parseOption('listId');
        $emailAddress = $this->parseOption('emailAddress');

        $additionalFields = $this->replacePlaceholders($this->parseOption('additionalFields'));
        $interestGroups = array_filter(
            $this->replacePlaceholders($this->parseOption('interestGroups')),
            fn ($interestGroup) => $interestGroup !== false && $interestGroup !== ''
        );
        try {
            $this->mailChimpService->subscribe($listId, $emailAddress, $additionalFields, null, $interestGroups);
        } catch (MailChimpException $exception) {
            throw new FinisherException(sprintf('Failed to subscribe "%s" to list "%s"!', $emailAddress, $listId), 1418060900, $exception);
        }
    }

    /**
     * Recursively replaces "{<var>}" with variables from the form runtime
     *
     * @param array|string|null $field
     * @return array|string|null
     */
    protected function replacePlaceholders($field)
    {
        if ($field === null) {
            return null;
        }
        if (is_array($field)) {
            return array_map([$this, 'replacePlaceholders'], $field);
        }
        return preg_replace_callback('/{([^}]+)}/', function ($match) {
            return ObjectAccess::getPropertyPath($this->finisherContext->getFormRuntime(), $match[1]);
        }, $field);
    }
}
