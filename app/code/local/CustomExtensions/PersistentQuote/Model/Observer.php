<?php

class CustomExtensions_PersistentQuote_Model_Observer
{
    /**
     * Magento passes a Varien_Event_Observer object as
     * the first parameter of dispatched events.
     */
    public function checkForExistingQuoteAndIfFoundAddToSession(Varien_Event_Observer $observer)
    {
        if(Mage::getSingleton('customer/session')->isLoggedIn())
        {
            $customerData      = Mage::getSingleton('customer/session')->getCustomer();
            $currentCustomerID = $customerData->getId();

            if ( !empty($currentCustomerID) )
            {

                $resource       = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $tableName      = $resource->getTableName('quoteadv_customer');

                $query          = "SELECT * FROM `" . $tableName . "` WHERE customer_id = '" . $currentCustomerID . "' AND is_quote = 2 AND status = 0 ORDER BY updated_at DESC";
                $results = $readConnection->fetchAll($query);
                $mostRecentQuoteId = 0;
                if ( !empty($results) )
                {
                    foreach ( $results as $resultID => $resultData )
                    {
                        if (strtotime($resultData['updated_at']) > strtotime('- 72 hours') || strtotime($resultData['created_at']) > strtotime('- 72 hours')  )
                        {
                            $query   = "UPDATE `" . $tableName . "` SET updated_at = '" . date('Y-m-d H:i:s') . "' WHERE quote_id = '" . $resultData['quote_id'] . "' LIMIT 1";
                            $readConnection->query($query);
                            // Set Current Session Quote Variables to Re-Engage Quote in Open Session
                            $mostRecentQuoteId = $resultData['quote_id'];
                            Mage::getSingleton('customer/session')->setQuoteadvId($mostRecentQuoteId);
                            Mage::getSingleton('customer/session')->setQuoteId($mostRecentQuoteId);
                            break;
                        }
                    }
                }
            }
        }
    }
}