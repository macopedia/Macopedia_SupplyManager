<?php

class Macopedia_SupplyManager_Model_Attachment extends Mage_Core_Model_Abstract
{

    /**
     * Attachment status codes
     */
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    /**
     * Attachment direction (who sends the attachment request)
     * EM = Employee
     * SM = Supply Manager
     */
    const DIRECTION_EM_TO_SM = 1;
    const DIRECTION_SM_TO_EM = 2;

    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'macopedia_supplymanager_attachment';
    protected $_resourceName = 'macopedia_supplymanager/attachment';

    protected function _construct()
    {
        $this->_init('macopedia_supplymanager/attachment');
    }

    /**
     * Processing object before save data
     * Add status pending if not set
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        if (!$this->getId() && $this->getDirection() && $this->checkAttachment($this->getEmployeeId(), $this->getSupplyManagerId(), $this->getDirection())
        ) {
            Mage::throwException(Mage::helper('macopedia_supplymanager')->__('Attachment already exists.'));
        }
        return $this;
    }

    /**
     * Returns attachment between employee and supply manager
     *
     * @param      $employeeId
     * @param      $supplyManagerId
     * @param bool $filterApproved
     *
     * @return Varien_Object
     */
    public function getSupplyManagerAttachment($employeeId, $supplyManagerId, $direction, $filterApproved = false)
    {
        $attachmentsCollection = $this->getResourceCollection()
                ->addFieldToFilter('employee_id', $employeeId)
                ->addFieldToFilter('supply_manager_id', $supplyManagerId)
                ->addFieldToFilter('direction', $direction);
        if ($filterApproved) {
            $attachmentsCollection->addFieldToFilter('status', self::STATUS_APPROVED);
        }
        return $attachmentsCollection->getFirstItem();
    }

    /**
     * Checks if attachment exists between employee and supply manager
     *
     * @param      $employeeId
     * @param      $supplyManagerId
     * @param bool $filterApproved
     *
     * @return bool
     */
    public function checkAttachment($employeeId, $supplyManagerId, $direction, $filterApproved = false)
    {
        if ($this->getSupplyManagerAttachment($employeeId, $supplyManagerId, $direction, $filterApproved)->getId() > 0
        ) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve store where attachment was created
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return Mage::app()->getStore($this->getStoreId());
    }

    /**
     * Set store to customer
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return Mage_Customer_Model_Customer
     */
    public function setStore(Mage_Core_Model_Store $store)
    {
        $this->setStoreId($store->getId());
        $this->setWebsiteId($store->getWebsite()->getId());
        return $this;
    }

    /**
     * Check if Supply Manager already send invitation to employee
     * @param integer $supplyManagerId
     * @param string $employeeEmail
     * @param string $direction
     * @return boolean
     */
    public function checkEmailAttachment($supplyManagerId, $employeeEmail, $direction)
    {
        $count = $this->getCollection()
                ->addFieldToFilter('email', $employeeEmail)
                ->addFieldToFilter('supply_manager_id', $supplyManagerId)
                ->addFieldToFilter('direction', $direction)
                ->count();

        return $count > 0;
    }

    /**
     * Replace email in table to employee Id
     * @param Mage_Customer_Model_Customer $customer
     */
    public function changeEmailAttachments(Mage_Customer_Model_Customer $customer)
    {
        $collection = $this->getCollection()
                ->addFieldToFilter('email', $customer->getEmail())
        ;
        foreach ($collection as $entry)
        {
            $entry->setEmail(null)
                    ->setEmployeeId($customer->getId());
            try {
                $entry->save();
            } catch (Exception $ex) {
                Mage::log($ex->getMessage(), 7, 'exeption.log');
            }
        }
    }

}
