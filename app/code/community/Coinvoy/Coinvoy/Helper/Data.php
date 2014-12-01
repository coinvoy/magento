<?php


class Coinvoy_Coinvoy_Helper_Data extends Mage_Payment_Helper_Data
{
    const ADDRESS = 'payment/Coinvoy/bitcoin_address';
    const SECRET  = 'payment/Coinvoy/secret';

    function createInvoice($amount, $options = array()) {

        try{
            require_once(Mage::getBaseDir('lib') . '/Coinvoy/Coinvoy.php');
            $cv = new Coinvoy();

            $address = Mage::getStoreConfig(self::ADDRESS);
            $secret  = Mage::getStoreConfig(self::SECRET);
            $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();

            $response = $cv->payment($amount,$currency_code,$address,$options);
        }catch(Exception $e) {
            $response = new stdClass();
            $response->error = $e->getMessage();
        }

        return $response;
    }

    function validateIPN($invoiceID, $hash, $orderID, $secret) {
        try {
            require_once(Mage::getBaseDir('lib') . '/Coinvoy/Coinvoy.php');
            $cv = new Coinvoy();
            $secret  = Mage::getStoreConfig(self::SECRET);

            return $cv->validateNotification($invoiceID, $hash, $orderID, $secret);
        } catch(Exception $e) {
            return false;
        }
    }
}
