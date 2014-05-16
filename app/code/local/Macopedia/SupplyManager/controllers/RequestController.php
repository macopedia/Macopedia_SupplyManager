<?php

class Macopedia_SupplyManager_RequestController extends Mage_Core_Controller_Front_Action
{

    /**
     * List of current SM employees wishlist page
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Employees wishlist'));
        $this->_initLayoutMessages('customer/session');
        $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('sm-manager/request');
        }

        $this->renderLayout();
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
     * Add items from wishlist to shopping cart
     *
     */
    public function addToCartAction()
    {
        $itemIds = $this->getRequest()->getParam('item');

        if (!$this->_validateFormKey() || !$this->getRequest()->isPost() || empty($itemIds)) {
            return $this->_redirect('*/*/');
        }

        $messages = array();
        $addedItems = array();
        $notSalable = array();
        $hasOptions = array();

        $cart = Mage::getSingleton('checkout/cart');
        $collection = Mage::getModel('wishlist/item')->getCollection()
            ->addFieldToFilter('wishlist_item_id', array('in' => $itemIds))
            ->setVisibilityFilter();

        $helper = Mage::helper('macopedia_supplymanager');
        foreach ($collection as $item) {
            /** @var Mage_Wishlist_Model_Item */
            if ($helper->checkPermissions($item)) {

                try {
                    $disableAddToCart = $item->getProduct()->getDisableAddToCart();
                    $item->unsProduct();
                    $item->getProduct()->setDisableAddToCart($disableAddToCart);
                    $item->loadWithOptions($item->getId());
                    $wishlist = Mage::getModel('wishlist/wishlist')->load($item->getWishlistId());
                    if ($item->addToCart($cart, true)) {
                        $customer = Mage::getModel('customer/customer')->load($wishlist->getCustomerId());
                        if ($item->getProduct()->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                            $groupedProducts = Mage::getModel("catalog/product_type_grouped")
                                ->setProduct($item->getProduct())->getAssociatedProducts();
                            foreach ($groupedProducts as $groupedProduct) {
                                $quoteItem = $cart->getQuote()->getItemByProduct($groupedProduct);
                                if ($quoteItem) {
                                    $this->_addRequestedByOption($quoteItem, $customer);
                                }
                            }
                        } else {
                            $quoteItem = $cart->getQuote()->getItemByProduct($item->getProduct());
                            if ($quoteItem) {
                                $this->_addRequestedByOption($quoteItem, $customer);
                            }
                        }
                        $addedItems[] = $item->getProduct();
                    }


                    if ($wishlist->getItemsCount() < 1) {
                        $wishlist->delete();
                    }
                } catch (Mage_Core_Exception $e) {
                    if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE) {
                        $notSalable[] = $item;
                    } else {
                        if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                            $hasOptions[] = $item;
                        } else {
                            $messages[] = $this->__(
                                '%s for "%s".', trim($e->getMessage(), '.'), $item->getProduct()->getName()
                            );
                        }
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                    $messages[] = Mage::helper('wishlist')->__('Cannot add the item to shopping cart.');
                }
            } else {
                $messages[] = Mage::helper('wishlist')->__(
                    'Not enough rights to add the following product to shopping cart: %s.',
                    $item->getProduct()->getName()
                );
            }
        }

        if (Mage::helper('checkout/cart')->getShouldRedirectToCart()) {
            $redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
        } else {
            $redirectUrl = $this->_getRefererUrl();
        }

        if ($notSalable) {
            $products = array();
            foreach ($notSalable as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = Mage::helper('wishlist')->__(
                'Unable to add the following product(s) to shopping cart: %s.', join(', ', $products)
            );
        }

        if ($hasOptions) {
            $products = array();
            foreach ($hasOptions as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = Mage::helper('wishlist')->__(
                'Product(s) %s have required options. Each of them can be added to cart separately only.',
                join(', ', $products)
            );
        }

        if ($messages) {
            $isMessageSole = (count($messages) == 1);
            if ($isMessageSole && count($hasOptions) == 1) {
                $item = $hasOptions[0];
                $redirectUrl = $item->getProductUrl();
            } else {
                $wishlistSession = Mage::getSingleton('checkout/session');
                foreach ($messages as $message) {
                    $wishlistSession->addError($message);
                }
            }
        }

        if ($addedItems) {
            // save wishlist model for setting date of last update

            $products = array();
            foreach ($addedItems as $product) {
                $products[] = '"' . $product->getName() . '"';
            }

            Mage::getSingleton('checkout/session')->addSuccess(
                Mage::helper('wishlist')->__(
                    '%d product(s) have been added to shopping cart: %s.', count($addedItems), join(', ', $products)
                )
            );
        }
        // save cart and collect totals
        $cart->save()->getQuote()->collectTotals();

        Mage::helper('wishlist')->calculate();

        $this->_redirectUrl($redirectUrl);
    }

    /**
     * Remove Item from SM and EM WishList
     */
    public function removeItemAction()
    {
        $id = (int)$this->getRequest()->getParam('item');
        $item = Mage::getModel('wishlist/item')->load($id);
        if (!$item->getId()) {
            return $this->norouteAction();
        }
        $helper = Mage::helper('macopedia_supplymanager');
        $wishlist = Mage::getModel('wishlist/wishlist')->load($item->getWishlistId());
        if (!$wishlist || !$helper->checkPermissions($item)) {
            Mage::getSingleton('customer/session')->addError(
                $this->__("You don't have permissions, to this item")
            );
            return $this->norouteAction();
        }
        try {
            $item->delete();
            $wishlist->save();
            Mage::getSingleton('customer/session')->addSuccess(
                $this->__('Item removed successfully.')
            );
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                $this->__('An error occurred while deleting the item from wishlist: %s', $e->getMessage())
            );
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                $this->__('An error occurred while deleting the item from wishlist.')
            );
        }

        $this->_redirectReferer(Mage::getUrl('*/*'));
    }

    /**
     * Add "Requested by" option to Quote Item to be able to track who requested the product
     * @param $quoteItem
     * @param $customer
     */
    protected function _addRequestedByOption($quoteItem, $customer)
    {
        $additionalOptions = array(
            array(
                'code'  => 'requested_by',
                'label' => Mage::helper('macopedia_supplymanager')->__('Requested By'),
                'value' => $customer->getName() . " (" . $customer->getEmail() . ")"
            )
        );
        $quoteItem->addOption(
            array(
                'code'  => 'additional_options',
                'value' => serialize($additionalOptions),
            )
        );
    }

}
