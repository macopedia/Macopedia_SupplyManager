<?php


class Macopedia_SupplyManager_Block_Request_List extends Mage_Core_Block_Template
{
    /**
     * Employee Collection
     *
     * @var Array
     */
    protected $_employeeIds = array();

    /**
     * Current SM employees Wishlist Collection
     *
     * @var Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected $_wishlistsCollection;

    /**
     * Get employee IDs for current SM
     *
     * @return Array
     */
    public function getEmployeeIds()
    {
        if (!$this->_employeeIds) {
            $supplyManagerId = Mage::getSingleton('customer/session')->getId();

            if (intval($supplyManagerId) > 0) {
                $employeeAttachments = Mage::getModel('macopedia_supplymanager/attachment')->getCollection()
                    ->getEmployees($supplyManagerId, true);
                foreach ($employeeAttachments as $employeeAttachment) {
                    $this->_employeeIds[] = $employeeAttachment->getEmployeeId();
                }
            }
        }
        return $this->_employeeIds;
    }

    /**
     * Get employee wishlist for current SM
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getEmployeeWishlists()
    {
        if ($this->getEmployeeIds()) {
            if (!$this->_wishlistsCollection) {
                $this->_wishlistsCollection = Mage::getModel('wishlist/wishlist')->getCollection()
                    ->addFieldToFilter('customer_id', array('in', $this->getEmployeeIds()));
            }
            return $this->_wishlistsCollection;
        }
        return new Varien_Data_Collection();
    }


    /**
     * Initiate and inject pager
     *
     * @return $this|Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $pager = $this->getLayout()->createBlock('page/html_pager', 'sm_requests_toolbar_pager')
            ->setCollection($this->getEmployeeWishlists());

        $limit = $this->getRequest()->getParam('limit') ? : Mage::getStoreConfig(Macopedia_SupplyManager_Model_Config::XML_PATH_SM_PAGINATION_DEFAULT);
        $availData = explode(',', Mage::getStoreConfig(Macopedia_SupplyManager_Model_Config::XML_PATH_SM_PAGINATION_AVAILABLES));
        $availables = array();

        foreach ($availData as $a) {
            $availables[$a] = $a;
        }
        
        $pager->setLimit($limit);
        $pager->setAvailableLimit($availables);
        $this->setChild('pager', $pager);
        $this->getEmployeeWishlists()->setPageSize($limit);
        $this->getEmployeeWishlists()->load();
        parent::_prepareLayout();
        return $this;
    }

    /**
     * Get pager html :)
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }


}