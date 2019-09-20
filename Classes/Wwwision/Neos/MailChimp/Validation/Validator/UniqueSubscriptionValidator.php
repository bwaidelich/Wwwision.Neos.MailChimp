<?php
declare(strict_types=1);

namespace Wwwision\Neos\MailChimp\Validation\Validator;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Validation\Validator\EmailAddressValidator;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;

/**
 * Validator for email addresses
 *
 * @api
 * @Flow\Scope("singleton")
 */
class UniqueSubscriptionValidator extends EmailAddressValidator
{
    /**
     * @Flow\Inject
     * @var MailChimpService
     */
    protected $mailChimpService;

    /**
     * @var array
     */
    protected $supportedOptions = [
        'listId' => [null, 'MailChimp List ID', 'string', true]
    ];

    /**
     * Checks if the given value is a valid email address.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @throws \Neos\Flow\Http\Exception
     * @throws \Wwwision\Neos\MailChimp\Exception\MailChimpException
     * @api
     */
    protected function isValid($value)
    {
        $options = $this->getOptions();
        if ($this->validEmail($value) && $this->mailChimpService->isMember($options['listId'], $value)) {
            $this->addError('This email address is already registered in our newsletter.', 1422317184);
        }
    }

}
