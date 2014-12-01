<?php

class Coinvoy_Coinvoy_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'Coinvoy';

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway               = true;

    /**
     * Can authorize online?
     */
    protected $_canAuthorize            = true;

    /**
     * Can capture funds online?
     */
    protected $_canCapture              = false;

    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial       = false;

    /**
     * Can refund online?
     */
    protected $_canRefund               = false;

    /**
     * Can void transactions online?
     */
    protected $_canVoid                 = false;

    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal          = true;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout          = true;

    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping  = true;

    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;


    public function authorize(Varien_Object $payment, $amount)
    {

      $cv_helper = Mage::helper('Coinvoy');
      $address   = Mage::getStoreConfig('payment/Coinvoy/bitcoin_address');
      $secret    = Mage::getStoreConfig('payment/Coinvoy/secret');
      $email     = Mage::getStoreConfig('payment/Coinvoy/email');
      $cvUrl     = Mage::getUrl('coinvoy_coinvoy');
      $storeName = Mage::app()->getStore()->getName();
      $order     = $payment->getOrder();
      $orderId   = $order->getId();
      $items = $order->getAllVisibleItems();
      $itemNames = "Items: ";
      foreach($items as $i) {
        $itemNames .= $i->getProductId().". ";
      }

      //PROCESS SECRET
      if(!$secret) {
        // generate
        $secret = hash('sha256', $address.mt_rand());
        // save
        Mage::getModel('core/config')->saveConfig('payment/Coinvoy/secret', $secret)->cleanCache();
        Mage::app()->getStore()->resetConfig();
      }
      // CHECK BITCOIN ADDRESS
      if(!$address) {
        throw new Exception("Before using the Coinvoy plugin, you need to enter an bitcoin address in Magento Admin > Configuration > System > Payment Methods > Coinvoy.");
      }

      // //$successUrl = Mage::getStoreConfig('payment/Coinvoy/custom_success_url');
      // //$cancelUrl = Mage::getStoreConfig('payment/Coinvoy/custom_cancel_url');
      $successUrl = $cvUrl. 'redirect/success/';
      $cancelUrl = $cvUrl. 'redirect/cancel/';

      $params = array(
          'secret'   => $secret,
          'orderID'  => $orderId,
          'company' => $storeName,
          'email'    => $email,
          //'item'     => $itemNames,
          'description' => 'Purchase',
          'callback' => $cvUrl. 'callback/notify/',
      );

      // // Generate the code
      try {
        $invoice = $cv_helper->createInvoice($amount, $params);
      } catch (Exception $e) {
        throw new Exception("Could not generate checkout page. Double check your Magento Configuration. Error message: " . $e->getMessage());
      }
      $redirectUrl = 'http://178.62.254.129/paymentPage/'.$invoice->id.'?redirect='.$successUrl;

      // Step 2: Redirect customer to payment page
      $payment->setIsTransactionPending(true); // Set status to Payment Review while waiting for Coinvoy postback
      Mage::getSingleton('customer/session')->setRedirectUrl($redirectUrl);

      return $this;
    }


    public function getOrderPlaceRedirectUrl()
    {
      return Mage::getSingleton('customer/session')->getRedirectUrl();
    }
}
?>
