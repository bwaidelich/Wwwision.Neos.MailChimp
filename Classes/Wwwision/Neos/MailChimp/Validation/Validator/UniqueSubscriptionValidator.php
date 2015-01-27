<?php
namespace Wwwision\Neos\MailChimp\Validation\Validator;

/*                                                                          *
 * This script belongs to the TYPO3 Flow package "Wwwision.Neos.MailChimp". *
 *                                                                          *
 *                                                                          */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Validation\Validator\EmailAddressValidator;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;

/**
 * Validator for email addresses
 *
 * @api
 * @Flow\Scope("singleton")
 */
class UniqueSubscriptionValidator extends EmailAddressValidator {

	/**
	 * @Flow\Inject
	 * @var MailChimpService
	 */
	protected $mailChimpService;

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'listId' => array(NULL, 'MailChimp List ID', 'string', TRUE)
	);

	/**
	 * Checks if the given value is a valid email address.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		$options = $this->getOptions();
		if ($this->validEmail($value) && $this->mailChimpService->isMember($options['listId'], $value)) {
			$this->addError('This email address is already registered in our newsletter.', 1422317184);
		}
	}

}
