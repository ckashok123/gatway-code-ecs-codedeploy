<?php

/**
 * Dimebox_Payment module dependency
 *
 * @category    Payment
 * @package     Dimebox_Payment
 * @author      Chetu
 * @copyright   Dimebox Payment
 */

namespace Dimebox\Payment\Controller\Iframe;

use Magento\Quote\Api\CartManagementInterface;

class Saveorder extends \Magento\Framework\App\Action\Action {

    protected $cartManagement;
    protected $messageManager;
    protected $_config;
    protected $_storeManager;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataFactory $dataFactory
     * @param CartManagementInterface $cartManagement
     * @param Onepage $onepageCheckout
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context, 
        CartManagementInterface $cartManagement, 
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logs,
        \Dimebox\Payment\Model\Extension $extension, 
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Dimebox\Payment\Model\Config $config,
         \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->cartManagement = $cartManagement;
        $this->messageManager = $messageManager;
        $this->_logs = $logs;
        $this->_extension = $extension;
        $this->quoteManagement = $quoteManagement;
        $this->_checkoutSession = $checkoutSession;
        $this->_config = $config;
        $this->_storeManager = $storeManager;

        parent::__construct($context);
    }

    public function execute() {
        if (isset($_REQUEST['PaRes'])) {
            $pares = $_REQUEST['PaRes'];
        }
        if (isset($_REQUEST['MD'])) {
            $md = $_REQUEST['MD'];
        }
        $this->_checkoutSession->getData('quote_id_1');
        $quote = $this->_objectManager->create('Magento\Quote\Model\Quote')->load($this->_checkoutSession->getData('quote_id_1'));
        $quoteData = $this->_checkoutSession->getQuote(); 
		if(isset($_COOKIE["customer_email"])){
			$email = $_COOKIE["customer_email"];
		} else {
			$email = "";
		}
		if(empty($quoteData->getBillingAddress()->getEmail())){
			$quote->setCheckoutMethod('guest')
					->setCustomerId(null)
					->setCustomerEmail($email)
					->setCustomerIsGuest(true)
					->setCustomerGroupId(0);
		}
		$this->_logs->log(\Psr\Log\LogLevel::INFO, 'get customer email from quote======' . print_r($quoteData->getBillingAddress()->getData(), true));
		
		 
		$this->_logs->log(\Psr\Log\LogLevel::INFO, '===========Save Email Order===================' . print_r($quote->getBillingAddress()->getData(), true));
		
		$this->_logs->log(\Psr\Log\LogLevel::INFO, '===========Checkout method===================' . print_r($quote->getCheckoutMethod(), true));
		
		$quoteId = $quote->getId();
        $tokenId = $this->_checkoutSession->getDimeboxCardId();
        $quote->setPaymentMethod('dimebox_payment'); //payment method
        
        $quote->getPayment()->importData(
                array(
                    'method' => 'dimebox_payment',
                    'cc_type' => $this->_checkoutSession->getDimeboxCardType(),
                    'cc_number' => $this->_checkoutSession->getDimeboxCardNumber(),
                    'cc_exp_year' => $this->_checkoutSession->getDimeboxCardYear(),
                    'cc_exp_month' => $this->_checkoutSession->getDimeboxCardMonth(),
                    'cc_cid' => $this->_checkoutSession->getDimeboxCardCVV()
                )
        );
        
        $authanticationStatus = $this->checkAuthanticationStatus($pares);
      //  $this->_logs->log(\Psr\Log\LogLevel::INFO, 'forth step response' . print_r($authanticationStatus, true));


        $authStatus = $authanticationStatus->authentication->status;
		
		if ($authStatus == 'Y' || $authStatus == 'A') {
            $payment_status = $this->doPayment($quoteData->getShippingAddress(), $pares, $quoteData->getGrandTotal(), $tokenId);
            $payment_status = json_decode($payment_status);
            if (!isset($payment_status->_id)) {
                $this->messageManager->addError(__($payment_status->message));
                
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }
            $this->_checkoutSession->setLastSuccessQuoteId($quoteId);
            $this->_checkoutSession->setLastQuoteId($quoteId);
            $order = $this->cartManagement->submit($quote);
			
        } else {
			if($authStatus == 'N') {
            $this->messageManager->addError(__("The cardholder authentication failed."));
            } 
			if($authStatus == 'U') {
            $this->messageManager->addError(__("Authentication could not be performed (liability is not shifted to the issuer)"));
            } 
			if($authStatus == '') {
            $this->messageManager->addError(__("There was an error during 3DS Authentication."));
            } 

            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
		}
        
        if ($order) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $orderDatamodel = $objectManager->get('Magento\Sales\Model\Order')->getCollection()->getLastItem();
            $orderId = $orderDatamodel->getId();
            $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
            $this->_checkoutSession->setLastOrderId($order->getIncrementId());
            $redirectUrl = $quote->getPayment()->getOrderPlaceRedirectUrl();

            // add order information to the session
            $this->_checkoutSession
                    ->setLastOrderId($order->getIncrementId())
                    ->setRedirectUrl($redirectUrl)
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());

            $quote->setIsActive(false)->save();
			
			$this->_checkoutSession->unsPareqSession();
			$this->_checkoutSession->unsActionUrlSession();
			$this->_checkoutSession->unsEnrolmentStatusSession();
			$this->_checkoutSession->unsDimeboxCardId();
			unset($_COOKIE["customer_email"]);
            $this->messageManager->addSuccess(__("You have successfully placed the order."));
            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
        }
    }

    public function doPayment($address, $pares, $amount, $card) {
	//$this->_logs->log(\Psr\Log\LogLevel::INFO, "customer data to call API  " . print_r($address->getData(), true));
        $requestCustomerDetail = $this->setCustomerData();
        $responseCustomerDetail = $this->_extension->getSessionTagApiResponse($requestCustomerDetail, $this->_config->getPaymentConfigData('gateway_url') . '/' . $this->_config->getPaymentConfigData('customer'));

        if (!empty($responseCustomerDetail->_id)) {
            $responseCustomerId = $responseCustomerDetail->_id;
        } else {
            $responseCustomerId = "";
        }

        $currency_code = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        if ($currency_code == "EUR" || $currency_code == "USD") {
            $amount = $amount * 100;
        }

        if ($this->_config->getPaymentConfigData('payment_action') == "authorize_capture") {
            $captureNow = true;
        } else {
            $captureNow = false;
        }
        $email = $address->getEmail();
	   if(empty($email)) {
		   $email = $_COOKIE["customer_email"];
	   }
        $request_enrolment = array(
            'account' => $this->_config->getPaymentConfigData('account_id'),
            'amount' => $amount,
            'capture_now' => $captureNow,
            'card' => $card,
            'customer' => $responseCustomerId,
            'customer_ip' => $_SERVER['REMOTE_ADDR'],
            'details' =>
            array(
                'redirect_url' => '',
            ),
            'dynamic_descriptor' => $this->_config->getPaymentConfigData('dynamic_descriptor'),
            'merchant_reference' => $this->_config->getPaymentConfigData('merchant_id'),
            'pares' => $pares,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'webhook_transaction_update' => '',
            'shipping_information' => array(
                'first_name' => $address->getFirstName(),
                'last_name' => $address->getLastName(),
                'email' => $email,
                'address' => $address->getStreetLine(1),
                'city' => $address->getCity(),
                'state' => $address->getRegion(),
                'postal_code' => $address->getPostcode(),
                'country' => $address->getCountryId(),
                'phone' => $address->getTelephone()
            ),
            'shopper_interaction' => $this->_config->getPaymentConfigData('shopper_interaction')
        );

       // $this->_logs->log(\Psr\Log\LogLevel::INFO, "payment request  " . print_r($request_enrolment, true));
        $xapi = $this->_config->getPaymentConfigData('x_apikey');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->_config->getPaymentConfigData('gateway_url') . '/' . $this->_config->getPaymentConfigData('transaction'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($request_enrolment),
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json",
                "X-APIKEY: $xapi"
            )
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        
        return $response;
    }

    public function setCustomerData() {
        $quoteData = $this->_checkoutSession->getQuote();
        $billing = $quoteData->getBillingAddress();
		$email = $billing->getEmail();
		if(empty($email)) {
		   $email = $_COOKIE["customer_email"];
		}
        $request_customer = [
            "city" => $billing->getCity(),
            "country_code" => $billing->getCountryId(),
            "email_address" => $email,
            "first_name" => $billing->getFirstname(),
            "last_name" => $billing->getLastname(),
            "organisation" => $this->_config->getPaymentConfigData('organization_id'),
            "phone_number" => $billing->getTelephone(),
            "postal_code" => $billing->getPostcode(),
            "region" => $billing->getRegionId(),
            "street_address" => $billing->getStreetLine(1)
        ];
//	$this->_logs->log(\Psr\Log\LogLevel::INFO, "customer data to call API  " . print_r($billing->getData(), true));
        return $request_customer;
    }

    public function checkAuthanticationStatus($pares = null) {
        $authId = $this->_checkoutSession->getAuthIdSession();
        $url = $this->_config->getPaymentConfigData('gateway_url').'/3d/'.$authId.'/authenticate';
        $requestAuthPares = array(
            'pares' => $pares
        );
        
        return $responseAuthPares = $this->_extension->getSessionTagApiResponse($requestAuthPares, $url);
    }

}
