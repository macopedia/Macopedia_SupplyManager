<?php

class Macopedia_SupplyManager_Block_Employee_List extends Mage_Core_Block_Template
{
    /**
     * Get employees for current SM
     *
     * @return ArrayObject|Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getEmployeeCollection()
    {
        $supplyManagerId = Mage::getSingleton('customer/session')->getId();

        if (intval($supplyManagerId) > 0) {
            $collection = Mage::getModel('macopedia_supplymanager/attachment')->getCollection()
                ->getEmployees($supplyManagerId, true);
            return $collection;
        }

        return new ArrayObject();
    }

    /**
     * Get pending approval requests from employees for current SM
     *
     * @return ArrayObject|Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getPendingEmployeeCollection()
    {
        $supplyManagerId = Mage::getSingleton('customer/session')->getId();

        if (intval($supplyManagerId) > 0) {
            $collection = Mage::getModel('macopedia_supplymanager/attachment')->getCollection()
                ->getEmployees($supplyManagerId);
            $collection->addFieldToFilter('status', Macopedia_SupplyManager_Model_Attachment::STATUS_PENDING)
                ->addFieldToFilter('direction', Macopedia_SupplyManager_Model_Attachment::DIRECTION_EM_TO_SM);
            return $collection;
        }

        return new ArrayObject();
    }
}