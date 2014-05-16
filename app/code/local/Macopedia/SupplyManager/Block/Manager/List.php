<?php

class Macopedia_SupplyManager_Block_Manager_List extends Mage_Core_Block_Template
{
    /**
     * Get approved SM for current customer
     *
     * @return ArrayObject|Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getManagersCollection()
    {
        $employeeId = Mage::getSingleton('customer/session')->getCustomer()->getId();

        if ($employeeId > 0) {
            $collection = Mage::getModel('macopedia_supplymanager/attachment')->getCollection()->getSupplyManagers($employeeId, true);
            return $collection;
        }
        return new Mage_Eav_Model_Entity_Collection();
    }

    /**
     * Get pending SM for current customer
     *
     * @return ArrayObject|Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getPendingManagersCollection()
    {
        $employeeId = Mage::getSingleton('customer/session')->getCustomer()->getId();

        if ($employeeId > 0) {
            $collection = Mage::getModel('macopedia_supplymanager/attachment')->getCollection()->getSupplyManagers($employeeId);
            $collection->addFieldToFilter('status', Macopedia_SupplyManager_Model_Attachment::STATUS_PENDING)
                ->addFieldToFilter('direction', Macopedia_SupplyManager_Model_Attachment::DIRECTION_SM_TO_EM);
            return $collection;
        }
        return new Mage_Eav_Model_Entity_Collection();
    }
}