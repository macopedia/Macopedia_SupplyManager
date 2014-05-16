<?php

/*
 * The MIT License
 *
 * Copyright 2014 Paweł Cieślik <p.cieslik@macopedia.pl>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Class Macopedia_SupplyManager_Model_Observer
 *
 * @author Paweł Cieślik <p.cieslik@macopedia.pl>
 */
class Macopedia_SupplyManager_Model_Observer
{

    /**
     * Function invoked by customer_register_success event, adding supply manager if filled and exists
     * @param Varien_Event_Observer $event
     * @return \Observer
     */
    public function getCustomerRegisterSuccess(Varien_Event_Observer $event)
    {
        $accountController = $event->getAccountController();
        $employee = $event->getCustomer();
        $supplyManagerEmail = $accountController->getRequest()->getPost('supplyManagerEmail');

        Mage::getModel('macopedia_supplymanager/attachment')->changeEmailAttachments($employee);

        if (empty($supplyManagerEmail)) {
            return;
        }

        $supplyManager = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getWebsite()->getId())
                ->loadByEmail($supplyManagerEmail);

        if (isset($supplyManager) && $supplyManager->getId() > 0) {

            $attachment = Mage::getModel('macopedia_supplymanager/attachment')->setId(null);
            $attachment->setSupplyManagerId($supplyManager->getId())
                    ->setDirection(Macopedia_SupplyManager_Model_Attachment::DIRECTION_EM_TO_SM)
                    ->setStatus(Macopedia_SupplyManager_Model_Attachment::STATUS_PENDING)
                    ->setEmployeeId($employee->getId())
            ;

            try {
                $attachment->save();
                $message = Mage::helper('macopedia_supplymanager')
                        ->__("Supply Manager %s was added to your dashboard and waiting for approval.", $supplyManagerEmail);
                Mage::helper('macopedia_supplymanager')->sendNotificationToManager($supplyManager, $employee, $attachment->getId());

                Mage::getSingleton('customer/session')->addSuccess($message);
            } catch (Mage_Core_Exception $ex) {
                Mage::log($ex->getMessage(), 3, 'exception.log');
            } catch (Exception $ex) {
                
            }
        } else {
            $message = Mage::helper('macopedia_supplymanager')
                    ->__("Supply Manager with email %s does not exists!", $supplyManagerEmail);
            Mage::getSingleton('customer/session')->addError($message);
        }

        return $this;
    }

    /**
     * Transfer additional options from Quote Item to Order Item
     * @param Varien_Event_Observer $observer
     */
    public function salesConvertQuoteItemToOrderItem(Varien_Event_Observer $observer)
    {
        $quoteItem = $observer->getItem();
        if ($additionalOptions = $quoteItem->getOptionByCode('additional_options')) {
            $orderItem = $observer->getOrderItem();
            $options = $orderItem->getProductOptions();
            $options['additional_options'] = unserialize($additionalOptions->getValue());
            $orderItem->setProductOptions($options);
        }
    }
}
