<?php

class Macopedia_SupplyManager_EmployeeController extends Mage_Core_Controller_Front_Action
{

    /**
     * List of employees for current SM
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Your employees'));
        $this->_initLayoutMessages('customer/session');
        $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('sm-manager/employee');
        }
        $this->renderLayout();
    }

    /**
     * Attach employee to supply manager
     */
    public function addPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }
        if ($this->getRequest()->isPost()) {
            $session = $this->_getSession();
            $session->setEscapeMessages(true);
            /** @var $supplyManager Mage_Customer_Model_Customer */
            $supplyManager = $session->getCustomer();
            $data = $this->getRequest();
            $employeeEmail = $data->getParam('email');
            if (!$employeeEmail) {
                $session->addError($this->__("Invalid email!"));
                $this->_redirect('*/*/');
                return $this;
            }
            if ($employeeEmail === $supplyManager->getEmail()) {
                $session->addError($this->__("You can't add yourself as employee!"));
                $this->_redirect('*/*/');
                return $this;
            }
            $employee = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getWebsite()->getId())
                    ->loadByEmail($employeeEmail);

            $employeeAttachment = Mage::getModel('macopedia_supplymanager/attachment');

            if (!$employee->getId()) {
                $employeeAttachment->setEmail($employeeEmail);
            } else {
                $employeeAttachment->setEmployeeId($employee->getId());
            }

            if ($employeeAttachment->checkAttachment($employee->getId(), $supplyManager->getId(), Macopedia_SupplyManager_Model_Attachment::DIRECTION_SM_TO_EM) || $employeeAttachment->checkEmailAttachment($supplyManager->getId(), $employeeEmail, Macopedia_SupplyManager_Model_Attachment::DIRECTION_SM_TO_EM)) {
                $session->addNotice($this->__("You already have %s in your list or waiting for approval.", $employeeEmail));
                $this->_redirect('*/*/');
                return $this;
            }
            $employeeAttachment->setSupplyManagerId($supplyManager->getId());
            $employeeAttachment->setStatus(Macopedia_SupplyManager_Model_Attachment::STATUS_PENDING);
            $employeeAttachment->setDirection(Macopedia_SupplyManager_Model_Attachment::DIRECTION_SM_TO_EM);
            try {
                $employeeAttachment->save();
                if (!$employee->getId()) {
                    Mage::helper('macopedia_supplymanager')->sendInvitationToEmployee(
                            $supplyManager, $employeeEmail
                    );
                } else {
                    Mage::helper('macopedia_supplymanager')->sendNotificationToEmployee(
                            $supplyManager, $employee, $employeeAttachment->getId()
                    );
                }
            } catch (Mage_Core_Exception $e) {
                $session->addError($e->getMessage());
                $this->_redirect('*/*/');
                return $this;
            }
            $session->addSuccess($this->__("Employee %s was added to your employee list and waiting for approval.", $employeeEmail));
            $this->_redirect('*/*/');
            return $this;
        }
        $this->_redirect('*/*/');
    }

    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Check if current customer is logged in
     *
     * @return Mage_Core_Controller_Front_Action|void
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Remove employee from list
     */
    public function removeAction()
    {
        if ($this->getRequest()->isPost()) {

            $attachmentId = $this->getRequest()->getParam('attachment_id');
            $supplyManagerId = Mage::getSingleton('customer/session')->getId();
            $this->getResponse()->setHeader('Content-type', 'text/json');

            if (intval($attachmentId) > 0) {
                $attachment = Mage::getModel('macopedia_supplymanager/attachment')->load($attachmentId);
                if (!$attachment->getId()) {
                    $msg = Mage::helper('macopedia_supplymanager')->__('You don\'t have that employee.');
                    $type = 'error';
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(compact('msg', 'type')));
                    return;
                }
                if ($attachment->getSupplyManagerId() != $supplyManagerId) {
                    $msg = Mage::helper('macopedia_supplymanager')->__('You cat\'t delete this attachment.');
                    $type = 'error';
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(compact('msg', 'type')));
                    return;
                }
                try {
                    $attachment->delete();

                    $msg = Mage::helper('macopedia_supplymanager')->__('Employee removed');
                    $type = 'success';
                } catch (Mage_Core_Exception $e) {
                    $msg = Mage::helper('macopedia_supplymanager')->__('Something went wrong, try again.');
                    $type = 'error';
                }
            }
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(compact('msg', 'type')));
        }

        return $this->_redirect('*/*/');
    }

    /**
     * Approve supply manager - employee connection
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function approveAction()
    {
        $attachmentId = $this->getRequest()->getParam('id');
        if ($attachmentId) {
            $session = $this->_getSession();
            $session->setEscapeMessages(true);
            $attachment = Mage::getModel('macopedia_supplymanager/attachment')->load($attachmentId);
            {
                if ($attachment->getEmployeeId() == $session->getCustomer()->getId()) {
                    $attachment->setStatus(Macopedia_SupplyManager_Model_Attachment::STATUS_APPROVED);
                    try {

                        $attachment->save();
                        $session->addSuccess($this->__("Supply manager request has been approved."));
                    } catch (Mage_Core_Exception $e) {
                        $session->addError($e->getMessage());
                        $this->_redirect('customer/account');
                    }
                } else {
                    $session->addError(
                            $this->__(
                                    "Unable to approve supply manager request, please check if you are logged in with correct account."
                            )
                    );
                }
            }
        }
        return $this->_redirect('customer/account');
    }

    /**
     * Reject supply manager - employee connection
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function rejectAction()
    {
        $attachmentId = $this->getRequest()->getParam('id');
        if ($attachmentId) {
            $session = $this->_getSession();
            $session->setEscapeMessages(true);
            $attachment = Mage::getModel('macopedia_supplymanager/attachment')->load($attachmentId);
            {
                if ($attachment->getEmployeeId() == $session->getCustomer()->getId()) {
                    $attachment->setStatus(Macopedia_SupplyManager_Model_Attachment::STATUS_REJECTED);
                    try {
                        $attachment->save();
                        $session->addSuccess($this->__("Supply manager request has been rejected."));
                    } catch (Mage_Core_Exception $e) {
                        $session->addError($e->getMessage());
                        $this->_redirect('customer/account');
                    }
                } else {
                    $session->addError(
                            $this->__(
                                    "Unable to reject supply manager request, please check if you are logged in with correct account."
                            )
                    );
                }
            }
        }
        return $this->_redirect('customer/account');
    }

    /**
     * Approve employee - supply manager connection
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function approveEmployeeAction()
    {
        $attachmentId = $this->getRequest()->getParam('id');
        if ($attachmentId) {
            $session = $this->_getSession();
            $session->setEscapeMessages(true);
            $attachment = Mage::getModel('macopedia_supplymanager/attachment')->load($attachmentId);
            {
                if ($attachment->getSupplyManagerId() == $session->getCustomer()->getId() && $attachment->getDirection() == Macopedia_SupplyManager_Model_Attachment::DIRECTION_EM_TO_SM) {
                    $attachment->setStatus(Macopedia_SupplyManager_Model_Attachment::STATUS_APPROVED);
                    try {

                        $attachment->save();
                        $session->addSuccess($this->__("Employee request has been approved."));
                    } catch (Mage_Core_Exception $e) {
                        $session->addError($e->getMessage());
                        $this->_redirect('customer/account');
                    }
                } else {
                    $session->addError(
                            $this->__(
                                    "Unable to approve the request, please check if you are logged in with correct account."
                            )
                    );
                }
            }
        }
        return $this->_redirect('*/*/');
    }

    /**
     * Reject employee - supply manager connection
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function rejectEmployeeAction()
    {
        $attachmentId = $this->getRequest()->getParam('id');
        if ($attachmentId) {
            $session = $this->_getSession();
            $session->setEscapeMessages(true);
            $attachment = Mage::getModel('macopedia_supplymanager/attachment')->load($attachmentId);
            {
                if ($attachment->getSupplyManagerId() == $session->getCustomer()->getId() && $attachment->getDirection() == Macopedia_SupplyManager_Model_Attachment::DIRECTION_EM_TO_SM) {
                    $attachment->setStatus(Macopedia_SupplyManager_Model_Attachment::STATUS_REJECTED);
                    try {
                        $attachment->save();
                        $session->addSuccess($this->__("Supply manager request has been rejected."));
                    } catch (Mage_Core_Exception $e) {
                        $session->addError($e->getMessage());
                        $this->_redirect('customer/account');
                    }
                } else {
                    $session->addError(
                            $this->__(
                                    "Unable to reject supply manager request, please check if you are logged in with correct account."
                            )
                    );
                }
            }
        }
        return $this->_redirect('customer/account');
    }

    /**
     * Attach supply manager to employee
     */
    public function addManagerPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }
        if ($this->getRequest()->isPost()) {
            $session = $this->_getSession();
            $session->setEscapeMessages(true);
            /** @var $employee Mage_Customer_Model_Customer */
            $employee = $session->getCustomer();
            $data = $this->getRequest();
            $managerEmail = $data->getParam('email');
            if (!$managerEmail) {
                $session->addError($this->__("Invalid email!"));
                $this->_redirect('customer/account');
                return $this;
            }
            if ($managerEmail === $employee->getEmail()) {
                $session->addError($this->__("You can't add yourself as supply manager!"));
                $this->_redirect('customer/account');
                return $this;
            }
            $supplyManager = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getWebsite()->getId())
                    ->loadByEmail($managerEmail);
            if (!$supplyManager->getId()) {
                $session->addError($this->__("Supply manager with email %s does not exists!", $managerEmail));
                $this->_redirect('customer/account');
                return $this;
            }
            $attachment = Mage::getModel('macopedia_supplymanager/attachment');

            if ($attachment->checkAttachment($supplyManager->getId(), $employee->getId(), Macopedia_SupplyManager_Model_Attachment::DIRECTION_EM_TO_SM)) {
                $session->addNotice($this->__("You already have %s in your list or waiting for approval.", $managerEmail));
                $this->_redirect('customer/account');
                return $this;
            }

            $attachment->setEmployeeId($employee->getId());
            $attachment->setSupplyManagerId($supplyManager->getId());
            $attachment->setStatus(Macopedia_SupplyManager_Model_Attachment::STATUS_PENDING);
            $attachment->setDirection(Macopedia_SupplyManager_Model_Attachment::DIRECTION_EM_TO_SM);
            try {
                $attachment->save();
                Mage::helper('macopedia_supplymanager')->sendNotificationToManager(
                        $supplyManager, $employee, $attachment->getId()
                );
            } catch (Mage_Core_Exception $e) {
                $session->addError($e->getMessage());
                $this->_redirect('customer/account');
                return $this;
            }
            $session->addSuccess($this->__("Supply manager %s was added to your supply managers list and waiting for approval.", $managerEmail));
            $this->_redirect('customer/account');
            return $this;
        }
        $this->_redirect('customer/account');
    }

}
