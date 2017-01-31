<?php
namespace Wwwision\Neos\MailChimp\Controller\Module;

use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Message;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Wwwision\Neos\MailChimp\Domain\Service\MailChimpService;
use Wwwision\Neos\MailChimp\Exception\MailChimpException;
use Wwwision\Neos\MailChimp\Exception\ResourceNotFoundException;

/**
 * Controller for the MailChimp Neos module
 */
class MailChimpController extends AbstractModuleController
{

    /**
     * @Flow\Inject
     * @var MailChimpService
     */
    protected $mailChimpService;

    /**
     * @return void
     */
    public function indexAction()
    {
        try {
            $this->view->assign('lists', $this->mailChimpService->getLists());
        } catch (MailChimpException $exception) {
            $this->addFlashMessage('An error occurred while trying to fetch lists from MailChimp: "%s"', 'Error', Message::SEVERITY_ERROR, [$exception->getMessage()]);
        }
    }

    /**
     * @param string $listId
     * @return void
     */
    public function listAction($listId)
    {
        $list = $this->fetchListById($listId);
        $this->view->assign('list', $list);
        try {
            $this->view->assign('members', $this->mailChimpService->getMembersByListId($listId));
        } catch (MailChimpException $exception) {
            $this->addFlashMessage('An error occurred while trying to fetch members for list "%s" from MailChimp: "%s"', 'Error', Message::SEVERITY_ERROR, [$list['name'], $exception->getMessage()]);
        }
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @return void
     */
    public function subscribeAction($listId, $emailAddress)
    {
        $list = $this->fetchListById($listId);
        try {
            $this->mailChimpService->subscribe($listId, $emailAddress);
        } catch (MailChimpException $exception) {
            $this->addFlashMessage('An error occurred while trying to subscribe the email "%s" to list "%s": "%s"', 'Error', Message::SEVERITY_ERROR, [$emailAddress, $list['name'], $exception->getMessage()]);
            $this->redirect('list', NULL, NULL, ['listId' => $list['id']]);
        }
        $this->addFlashMessage('Subscribed email "%s" to list "%s". Note: The user will receive an email to confirm the subscription!', 'Success!', Message::SEVERITY_OK, array($emailAddress, $list['name']));
        $this->redirect('list', NULL, NULL, ['listId' => $list['id']]);
    }

    /**
     * @param string $listId
     * @param string $emailAddress
     * @return void
     */
    public function unsubscribeAction($listId, $emailAddress)
    {
        $list = $this->fetchListById($listId);
        try {
            $this->mailChimpService->unsubscribe($listId, $emailAddress);
        } catch (MailChimpException $exception) {
            $this->addFlashMessage('An error occurred while trying to unsubscribe the email "%s" from list "%s": "%s"', 'Error', Message::SEVERITY_ERROR, array($emailAddress, $list['name'], $exception->getMessage()));
            $this->redirect('list', NULL, NULL, ['listId' => $list['id']]);
        }
        $this->addFlashMessage('Unsubscribed email "%s" from list "%s". Note: A goodbye-mail was sent to the user!', 'Success!', Message::SEVERITY_NOTICE, array($emailAddress, $list['name']));
        $this->redirect('list', NULL, NULL, ['listId' => $list['id']]);
    }

    /**
     * Helper function to fetch a MailChimp list by the given id
     *
     * @param string $listId
     * @return array
     */
    protected function fetchListById($listId)
    {
        try {
            return $this->mailChimpService->getListById($listId);
        } catch (ResourceNotFoundException $exception) {
            $this->addFlashMessage('The list with id "%s" does not exist', 'This list does not exist', Message::SEVERITY_WARNING, [$listId]);
            $this->redirect('index');
        } catch (MailChimpException $exception) {
            $this->addFlashMessage('An error occurred while trying to fetch list with id "%s" from MailChimp: "%s"', 'Error', Message::SEVERITY_ERROR, [$listId, $exception->getMessage()]);
            $this->redirect('index');
        }
        return null;
    }

}
