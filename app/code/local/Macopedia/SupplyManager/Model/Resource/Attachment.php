<?php

class Macopedia_SupplyManager_Model_Resource_Attachment extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('macopedia_supplymanager/attachment', 'attachment_id');
    }
}