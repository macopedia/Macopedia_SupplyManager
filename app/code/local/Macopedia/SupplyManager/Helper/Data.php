<?php

class Macopedia_SupplyManager_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Array of Mage_Wishlist_Model_Wishlist
     * @var array
     */
    protected $_wishlist = array();

    /**
     * Function send email notify from SM to Employee
     *
     * @param Mage_Customer_Model_Customer $supplyManager
     * @param Mage_Customer_Model_Customer $employee
     *
     * @return bool
     */
    public function sendNotificationToEmployee(
        Mage_Customer_Model_Customer $supplyManager, Mage_Customer_Model_Customer $employee, $attachmentId
    )
    {
        $approveLink = $this->_getUrl('sm-manager/employee/approve', array('id' => $attachmentId));
        $storeId = Mage::app()->getStore()->getId();
        $mailer = Mage::getModel('core/email_template_mailer');
        $sender = 'general';
        $mailer->setSender($sender);
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($employee->getEmail(), $employee->getName());
        $mailer->addEmailInfo($emailInfo);

        $mailer->setStoreId($storeId);
        $mailer->setTemplateId(Mage::getStoreConfig('supplymanager/emails/employee_approval_email', $storeId));

        $mailer->setTemplateParams(
            array(
                'supplyManagerName' => $supplyManager->getName(),
                'employeeName' => $employee->getName(),
                'approveLink' => $approveLink
            )
        );
        return $mailer->send();
    }

    /**
     * Function send email notify from SM to Employee
     *
     * @param Mage_Customer_Model_Customer $supplyManager
     * @param Mage_Customer_Model_Customer $employee
     *
     * @return bool
     */
    public function sendNotificationToManager(
        Mage_Customer_Model_Customer $supplyManager, Mage_Customer_Model_Customer $employee, $attachmentId
    )
    {
        $approveLink = $this->_getUrl('sm-manager/employee/approveEmployee', array('id' => $attachmentId));
        $storeId = Mage::app()->getStore()->getId();
        $mailer = Mage::getModel('core/email_template_mailer');
        $sender = 'general';
        $mailer->setSender($sender);
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($supplyManager->getEmail(), $supplyManager->getName());
        $mailer->addEmailInfo($emailInfo);

        $mailer->setStoreId($storeId);
        $mailer->setTemplateId(Mage::getStoreConfig('supplymanager/emails/manager_approval_email', $storeId));

        $mailer->setTemplateParams(
            array(
                'supplyManagerName' => $supplyManager->getName(),
                'employeeName' => $employee->getName(),
                'approveLink' => $approveLink
            )
        );
        return $mailer->send();
    }

    /**
     * Function check permissions supply manager to item
     *
     * @param Mage_Wishlist_Model_Item $item
     * @return bool
     */
    public function checkPermissions(Mage_Wishlist_Model_Item $item)
    {
        $supplyManagerId = Mage::getSingleton('customer/session')->getId();
        $employees_attachment = Mage::registry('supply_manager_employees_attachment');


        if (!$employees_attachment) {

            Mage::unregister('supply_manager_employees_attachment');
            $employees_attachment = Mage::getModel('macopedia_supplymanager/attachment')->getCollection()
                ->getEmployees($supplyManagerId, true);

            Mage::register('supply_manager_employees_attachment', $employees_attachment);
        }

        if (!isset($this->_wishlist[$item->getWishlistId()])) {

            $this->_wishlist[$item->getWishlistId()] = Mage::getModel('wishlist/wishlist')->load($item->getWishlistId());
        }

        foreach ($employees_attachment as $attachment)
        {
            if ($this->_wishlist[$item->getWishlistId()]->isOwner($attachment->getEmployeeId())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get attachment status string
     *
     * @param Macopedia_SupplyManager_Model_Attachment $attachment
     *
     * @return string
     */
    public function getAttachmentStatusString($attachment)
    {
        switch ($attachment->getStatus())
        {
            case Macopedia_SupplyManager_Model_Attachment::STATUS_APPROVED:
                return $this->__("Approved");
                break;
            case Macopedia_SupplyManager_Model_Attachment::STATUS_PENDING:
                return $this->__("Pending");
                break;
            case Macopedia_SupplyManager_Model_Attachment::STATUS_REJECTED:
                return $this->__("Rejected");
                break;
            default:
                return $this->__("Pending");
                break;
        }
    }

    /**
     * Function send email invitation from store to EM, to create account
     *
     * @param Mage_Customer_Model_Customer $supplyManager
     * @param string                       $employeeEmail
     *
     * @return bool
     */
    public function sendInvitationToEmployee(
        Mage_Customer_Model_Customer $supplyManager, $employeeEmail)
    {
        $registerLink = $this->_getUrl('customer/account/create');
        $storeId = Mage::app()->getStore()->getId();
        $mailer = Mage::getModel('core/email_template_mailer');
        $sender = 'general';
        $mailer->setSender($sender);
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($employeeEmail, $employeeEmail);
        $mailer->addEmailInfo($emailInfo);

        $mailer->setStoreId($storeId);
        $mailer->setTemplateId(Mage::getStoreConfig('supplymanager/emails/employee_invitation_email', $storeId));

        $mailer->setTemplateParams(
            array(
                'supplyManagerName' => $supplyManager->getName(),
                'registerLink' => $registerLink
            )
        );
        return $mailer->send();
    }

    /**
     * Check if wishlist item is configured correctly
     * @param Mage_Wishlist_Model_Item $item
     * @return type
     */
    public function checkWishlistItemIsNotSaleable($item)
    {
        $product = $item->getProduct();

        try {

            $instance = $product->getTypeInstance(true);
            $instance->checkProductBuyState($product);

            switch ($product->getTypeId())
            {
                default:
                case 'simple':
                case 'bundle':
                    /* Mage_Core_Exception is thrown no extra condition needed */
                    break;
                case 'grouped':
                    return !is_array($instance->processConfiguration($item->getBuyRequest(),$product, Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_FULL));
                case 'configurable':
                    return !empty($instance->checkProductConfiguration($product, $item->getBuyRequest()));
                case 'downloadable':
                    return !($instance->getLinkSelectionRequired($product) && !empty($product->getDownloadableLinks()));
            }
        } catch (Mage_Core_Exception $ex) {

            return true;
        }

        return false;
    }

}
