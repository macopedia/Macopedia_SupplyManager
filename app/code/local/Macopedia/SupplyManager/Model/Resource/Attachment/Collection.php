<?php

class Macopedia_SupplyManager_Model_Resource_Attachment_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('macopedia_supplymanager/attachment');
    }


    /**
     * Get supply managers for employee
     *
     * @param      $employeeId
     * @param bool $filterApproved
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getSupplyManagers($employeeId, $filterApproved = false)
    {
        $attachmentsCollection = $this->addFieldToSelect(array('employee_id', 'supply_manager_id', 'status'))
            ->addFieldToFilter('employee_id', $employeeId);
        if ($filterApproved) {
            $attachmentsCollection->addFieldToFilter(
                'status', Macopedia_SupplyManager_Model_Attachment::STATUS_APPROVED
            );
        }
        return $attachmentsCollection;
    }

    /**
     * Returns employees for supply manager
     *
     * @param      $supplyManagerId
     * @param bool $filterApproved
     *
     * @return Varien_Object
     */
    public function getEmployees($supplyManagerId, $filterApproved = false)
    {
        $attachmentsCollection = $this->addFieldToSelect(array('supply_manager_id', 'employee_id', 'status'))
            ->addFieldToFilter('supply_manager_id', $supplyManagerId);
        if ($filterApproved) {
            $attachmentsCollection->addFieldToFilter(
                'status', Macopedia_SupplyManager_Model_Attachment::STATUS_APPROVED
            );
        }
        return $attachmentsCollection;
    }

}