<?php
namespace Dimebox\Payment\Model;
/**
 * Pay In Store payment method model
 *
 * @category    Dime box
 * @package     Dime box
 * 
 */
class Extension extends \Magento\Payment\Model\Method\Cc {
    const CODE = 'dimebox_payment';
    const CCCAPTURE = 'ccCapture';
    const AUTHORIZATION = 'authorization';
    const AUTHANDCAPTURE = 'authAndCapture';
    protected $_logFactory;
    protected $_code = self::CODE;
    protected $_canCapture = true;
    protected $_canAuthorize = true;
    protected $_canCapturePartial = true;
    protected $_canUseCheckout = true;
    protected $_canFetchTransactionInfo = true;
    protected $_isGateway = true;
    protected $_canUseInternal = true;
    protected $_canVoid = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
    protected $_Config;
    protected $_Processor;
    protected $_Connection;
    protected $_remote;
    protected $_checkoutSession;
    protected $customerSession;
    protected $customerRepositoryInterface;
    protected $ruleRepositoryInterface;
    protected $messageFactory;
    protected $moduleManager;
    protected $_coreSession;
    /**
     * Request instance
     *
     * @var $_request
     * @var $helper
     * @var $_logger
     */
    protected $_request;
    protected $helper;
    protected $_logger;
    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     */
    public function __construct(
    \Magento\Framework\Model\Context $context, \Magento\Customer\Model\Session $customerSession, \Magento\Framework\Registry $registry, \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory, \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory, \Magento\Payment\Helper\Data $paymentData, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Payment\Model\Method\Logger $logger, \Psr\Log\LoggerInterface $logs, \Magento\Framework\Module\ModuleListInterface $moduleList, \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate, \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remote, \Magento\Checkout\Model\Session $checkoutSession, LogsFactory $logFactory, Config $config, \Magento\Checkout\Model\Cart $cart, \Dimebox\Payment\Model\Core\Processor $processor, \Dimebox\Payment\Model\Core\Connection $connection, \Dimebox\Payment\Model\Core\ServiceConfig $serviceConfig, \Magento\Store\Model\StoreManagerInterface $storeManager, \Dimebox\Payment\Helper\Data $helper, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface, \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository, \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepositoryInterface, \Magento\GiftMessage\Model\MessageFactory $messageFactory, \Magento\Framework\Module\Manager $moduleManager, \Magento\Framework\HTTP\Header $httpHeader,\Magento\Framework\Session\SessionManagerInterface $coreSession, \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null, \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null, array $data = array()
    ) {
        parent::__construct(
                $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $moduleList, $localeDate, null, null, $data
        );
        $this->httpHeader = $httpHeader;
        $this->moduleManager = $moduleManager;
        $this->messageFactory = $messageFactory;
        $this->ruleRepositoryInterface = $ruleRepositoryInterface;
        $this->helper = $helper;
        $this->_remote = $remote;
        $this->_moduleList = $moduleList;
        $this->_localeDate = $localeDate;
        $this->_paymentData = $paymentData;
        $this->_scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        $this->_logs = $logs;
        $this->_logFactory = $logFactory;
        $this->config = $config;
        $this->_checkoutSession = $checkoutSession;
        $this->_Config = $serviceConfig;
        $this->_Processor = $processor;
        $this->_Connection = $connection;
        $this->_storeManager = $storeManager;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerSession = $customerSession;
        $this->transactionRepository = $transactionRepository;
        $this->_cart = $cart;
        $this->_coreSession = $coreSession;
    }
    /**
     * Send capture request to gateway
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * 
     * @param float $amount
     * @return  $transactionId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment = null, $amount = null) {
	
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for authorization.'));
        }
        try {
            // payment type authrize/authorizecapture
            $payment_type = $this->config->getPaymentConfigData('payment_action');
            if (!$payment->getTransactionId() && ($payment_type == 'authorize_capture' )) {
                // For auth and capture process.
                $prepareRequestData = $this->_prepareRequestData($payment, $amount);
                $finalParams = array_merge(
                        $this->_setGatewayInformation(), $prepareRequestData
                );
                $payment_method = self::AUTHANDCAPTURE;
            } else {
                // Capture already authorized payment					
                $payment_method = self::CCCAPTURE;
                $currency_code = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
                if ($currency_code == "EUR" || $currency_code == "USD") {
                    $amount = $amount * 100;
                }
                $prepareRequestDataCap = array();
                $prepareRequestDataCap['amount'] = $amount;
                $transId = $payment->getAdditionalInformation('transaction_id');
                $capture_url = $this->config->getPaymentConfigData('gateway_url') . '/' . $this->config->getPaymentConfigData('transaction') . '/' . $transId . '/' . $this->config->getPaymentConfigData('capture');
                $finalParams = array_merge(
                        $this->_setAPIInformation($capture_url), $prepareRequestDataCap
                );
            }
            $parseResult = $this->_setApiMethodAndgetResponse($payment_method, $finalParams);
            if (!$payment->getTransactionId() && ($payment_type == 'authorize_capture')) {
                    $this->_logs->log(\Psr\Log\LogLevel::INFO, "payment with auth and capture has been integrated successfully");
                $this->responseAuthCapture($parseResult, $payment);
            } else {
                $this->responseccCapture($parseResult, $payment);
            }
        } catch (Exception $ex) {
            throw new LocalizedException(__('There was an error capturing the transaction: %1.', $ex->getMessage()));
        }
        return $this;
    }
    /**
     * Set transaction id as per get response from auth and capture payment method  
     * 
     * @param $parseResult
     * @param  $payment
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function responseAuthCapture($parseResult = null, $payment = null) {
		$parseResult->message = "";
        if (property_exists($parseResult, 'reason_code') && $parseResult->reason_code == 00) {
            $payment->setTransactionId($parseResult->_id);
            $payment->setAdditionalInformation('transaction_id', $parseResult->_id);
            $payment->setAdditionalInformation($parseResult->_id);
            $payment->setIsTransactionClosed(0);
        } else {
            $this->_logs->log(\Psr\Log\LogLevel::INFO, print_r($parseResult, true));
            throw new \Magento\Framework\Exception\LocalizedException(__($parseResult->message));
        }
    }
    /**
     * Handle response to capture already authorized payment
     * 
     * @param $parseResult
     * @param  $payment
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function responseccCapture($parseResult = null, $payment = null) {
        if ((property_exists($parseResult, 'reason_code') && $parseResult->reason_code == 00 && $parseResult->status == "SETTLEMENT_REQUESTED")) {
            if ($parseResult->_id != $payment->getParentTransactionId()) {
                $payment->setTransactionId($parseResult->_id);
            }
            $payment->setIsTransactionClosed(0);
            return $this;
        } else {
            $this->_logs->log(\Psr\Log\LogLevel::INFO, print_r($parseResult, true));
            throw new \Magento\Framework\Exception\LocalizedException(__($parseResult->message));
        }
    }
    /**
     * Send authorize request to gateway
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * 
     * @param float $amount
     * @return string $transactionId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment = null, $amount = null) {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for authorization.'));
        }
        try {
            $payment_type = $this->config->getPaymentConfigData('payment_action');
            if (!$payment->getTransactionId() && ($payment_type == 'authorize' )) {
                // For authorize process.
                $prepareRequestData = $this->_prepareRequestData($payment, $amount);
                $finalParams = array_merge(
                        $this->_setGatewayInformation(), $prepareRequestData
                );
				
				
                $payment_method = self::AUTHORIZATION;
                $parseResult = $this->_setApiMethodAndgetResponse($payment_method, $finalParams);
                if (property_exists($parseResult, 'reason_code') && $parseResult->reason_code == 00) {
                    $payment->setTransactionId($parseResult->_id);
                    $payment->setAdditionalInformation('transaction_id', $parseResult->_id);
                    $payment->setAdditionalInformation($parseResult->_id);
                    $payment->setIsTransactionClosed(0);
                    $this->_logs->log(\Psr\Log\LogLevel::INFO, 'Response after authorize  method');
                } else {
                    $this->_logs->log(\Psr\Log\LogLevel::INFO, print_r($parseResult, true));
                    throw new \Magento\Framework\Exception\LocalizedException(__($parseResult->message));
                }
            }
        } catch (Exception $ex) {
            throw new LocalizedException(__('There was an error capturing the transaction: %1.', $ex->getMessage()));
        }
        return $this;
    }
    /**
     * Refund the amount through gateway
     * 
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $transactionId
     * @throws \Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment = null, $amount = null) {
        try {
            if ($amount <= 0) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for refund.'));
            }
            if (!$payment->getParentTransactionId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid transaction ID.'));
            }
			$currency_code = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
			if ($currency_code == "EUR" || $currency_code == "USD") {
				$amount = $amount * 100;
			}
            $transId = $payment->getAdditionalInformation('transaction_id');
            $refund_url = $this->config->getPaymentConfigData('gateway_url') . '/' . $this->config->getPaymentConfigData('transaction') . '/' . $transId . '/' . $this->config->getPaymentConfigData('refund');
            $prepareRequestDataRefund = array(
                "amount" => $amount,
                "reason" => "test"
            );
            $finalParams = array_merge(
                    $this->_setAPIInformation($refund_url), $prepareRequestDataRefund
            );
            $this->_logs->log(\Psr\Log\LogLevel::INFO, print_r($finalParams, true));
            $parseResult = $this->_setApiMethodAndgetResponse('ccreverse', $finalParams);
			$this->_logs->log(\Psr\Log\LogLevel::INFO, "refund parse resust" . print_r($parseResult, true));
            //if (property_exists($parseResult, 'reason_code') && $parseResult->reason_code == 00) {
                if ($parseResult->_id != $payment->getParentTransactionId()) {
                    $payment->setTransactionId($parseResult->_id);
                }
                $payment->setIsTransactionClosed(1)
                        ->setShouldCloseParentTransaction(1);
                return $this;
            // } else {
                // $this->_logs->log(\Psr\Log\LogLevel::INFO, print_r($parseResult, true));
                // throw new \Magento\Framework\Exception\LocalizedException(__($parseResult->message));
            // }
        } catch (Exception $e) {
            $this->_logs->log(
                    \Psr\Log\LogLevel::INFO, "There was an error refunding the transaction:" . $e->getMessage()
            );
            throw new LocalizedException(__('There was an error refunding the transaction: %1.', $e->getMessage()));
        }
    }
    /**
     * void payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @return $this
     * 
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment) {
        try {
            if (!$this->canVoid()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for void.'));
            }
            if (!$payment->getParentTransactionId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid transaction ID.'));
            }
            $transId = $payment->getAdditionalInformation('transaction_id');
            $void_url = $this->config->getPaymentConfigData('gateway_url') . '/' . $this->config->getPaymentConfigData('transaction') . '/' . $transId . '/' . $this->config->getPaymentConfigData('void');
            $params = $this->_setAPIInformation($void_url);
            $parseResult = $this->_setApiMethodAndgetResponse('ccreverse', $params);
            if (property_exists($parseResult, 'reason_code') && $parseResult->reason_code == 00) {
                if ($parseResult->_id != $payment->getParentTransactionId()) {
                    $payment->setTransactionId($parseResult->_id);
                }
                $payment->setIsTransactionClosed(1)
                        ->setShouldCloseParentTransaction(1);
                return $this;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__($parseResult->message));
            }
        } catch (Exception $e) {
                throw new LocalizedException(__('There was an error to void the transaction: %1.', $e->getMessage()));
        }
    }
    /**
     * @return array $request
     * Use to set request data to call Dimebox API method 
     * @param $payment
     */
    public function _prepareRequestData($payment = null, $amount = null) {
        $requestParams = $this->setDimeboxAPIData($payment, $amount);
        return $requestParams;
    }
    /**
     * Use to get Dime box payment request data
     * 
     * @param null
     * @return array 
     */
    public function setDimeboxAPIData($payment = null, $amount = null) {
        // create object of payment to get all order related data
		// $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
		// $webhookTransactionUpdate = $baseUrl."rest/V1/transaction/update/?id=";
		// $this->_logs->log(\Psr\Log\LogLevel::INFO, "webhook transaction url" . print_r($webhookTransactionUpdate,true));
        $order = $payment->getOrder();
        $shipping = $order->getShippingAddress();
        if ((strlen($payment->getCcExpMonth())) < 2) {
            $exp_month = '0' . $payment->getCcExpMonth();
        } else {
            $exp_month = $payment->getCcExpMonth();
        }
        $currency_code = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        if ($currency_code == "EUR" || $currency_code == "USD") {
            $amount = $amount * 100;
        }
        $request_customer_detail = $this->setCustomerAPIData($payment);
        $response_customer_detail = $this->getSessionTagApiResponse($request_customer_detail, $this->config->getPaymentConfigData('gateway_url') . '/' . $this->config->getPaymentConfigData('customer'));
        $request_card_detail = $this->setCardTokenData($payment);
        $response_card = $this->getSessionTagApiResponse($request_card_detail, $this->config->getPaymentConfigData('gateway_url') . '/' . $this->config->getPaymentConfigData('card'));
        if ($this->config->getPaymentConfigData('payment_action') == "authorize_capture") {
            $captureNow = true;
        } else {
            $captureNow = false;
        }
        $userAgent = $this->httpHeader->getHttpUserAgent();
        if (!empty($response_card) && is_object($response_card) && isset($response_card->_id)) {
            if (!empty($response_customer_detail->_id)) {
                $response_customer_id = $response_customer_detail->_id;
            } else {
                $response_customer_id = "";
            }
			$checkSession = $this->_checkoutSession->getDimeboxCardId(); 
			$cardID = "";
			if(!empty($checkSession)){
				 $cardID = $checkSession;
				 $this->_logs->log(\Psr\Log\LogLevel::INFO, "card value from session == " . print_r($cardID,true));
			}else{
				$cardID = $response_card->_id;
				$this->_logs->log(\Psr\Log\LogLevel::INFO, "card value from API == " . print_r($cardID,true));
			}
			
             $setApiData = [
                "account" => $this->config->getPaymentConfigData('account_id'),
                "amount" => $amount,
                "capture_now" => $captureNow,
                "card" => $cardID,
                "customer" => $response_customer_id,
                "customer_ip" => $this->config->getIpAddress(),
                "details" => ["redirect_url" => ""],
                "dynamic_descriptor" => true,
                "merchant_reference" => $this->config->getPaymentConfigData('merchant_id'),
                "user_agent" => $userAgent,
                 "webhook_transaction_update" => '',
                 //"webhook_transaction_update" => $webhookTransactionUpdate,
                "shipping_information" => [
                    "first_name" => $shipping->getFirstname(),
                    "last_name" => $shipping->getLastname(),
                    "email" => $shipping->getEmail(),
                    "address" => $shipping->getStreetLine(1),
                    "city" => $shipping->getCity(),
                    "state" => $shipping->getRegionId(),
                    "postal_code" => $shipping->getPostcode(),
                    "country" => $shipping->getCountryId(),
                    "phone" => $shipping->getTelephone()
            
				],
                "shopper_interaction" => $this->config->getPaymentConfigData('shopper_interaction')
            ];
        }		
		return $setApiData;
    }
     /**
     * Set shipping Information to set API data 
     * 
     * @param null
     * @return array 
     */
    public function setShippingInformation($payment = null) {
        $order = $payment->getOrder();
        $shipping = $order->getShippingAddress();
        $shippingInformation = [
                    "first_name" => $shipping->getFirstname(),
                    "last_name" => $shipping->getLastname(),
                    "email" => $shipping->getEmail(),
                    "address" => $shipping->getStreetLine(1),
                    "city" => $shipping->getCity(),
                    "state" => $shipping->getRegionId(),
                    "postal_code" => $shipping->getPostcode(),
                    "country" => $shipping->getCountryId(),
                    "phone" => $shipping->getTelephone()
            
        ];
        return $shippingInformation;
    }
    
    /**
     * Use to set Dimebox initial requeired parameters
     * 
     * @param null
     * @return $requestParams
     */
    public function _setGatewayInformation() {
        return $requestParams = [
            'end_point' => $this->config->getPaymentConfigData('gateway_url') . '/' . $this->config->getPaymentConfigData('transaction')
        ];
    }
    /**
     * Use to set Dime box initial required parameters
     * 
     * @param null
     * @return $requestParams
     */
    public function _setAPIInformation($endpoint) {
        return $requestParams = [
            'end_point' => $endpoint,
        ];
    }
    /**
     * Set method for coder SDK and get response
     * $requestParams
     * @return string $parseResult 
     */
    public function _setApiMethodAndgetResponse($methodName = null, $requestParams = array()) {
        $this->_Config->serviceConfig($requestParams, $this->_Connection);
        $this->_Processor->setServiceConfig($this->_Config);
        $response = $this->_Processor->setMethodName($methodName)->getResponse();
        return $parseResult = json_decode($response);
    }
    /**
     * Use to get Dime box sessionTag API response
     * 
     * @param null
     * @return $parseResult
     */
    public function getSessionTagApiResponse($requestParams, $apiUrl) {
        $response = $this->_Config->getSessionTags($requestParams, $apiUrl);
        return $parseResult = json_decode($response);
    }
    /**
     * Use to set Customer API required parameters
     * 
     * @param null
     * @return $requestParams
     */
    public function setCustomerAPIData($payment = null) {
        $order = $payment->getOrder();
		$transId = $order->getPayment()->getLastTransId();
         $billing = $order->getBillingAddress();
        $customerId = $this->customerSession->getCustomer()->getId();
        if (!empty($customerId)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customer = $objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
        }
        $request_customer = [
            "city" => $billing->getCity(),
            "country_code" => $billing->getCountryId(),
            "email_address" => $billing->getEmail(),
            "first_name" => $billing->getFirstname(),
            "last_name" => $billing->getLastname(),
            "organisation" => $this->config->getPaymentConfigData('organization_id'),
            "phone_number" => $billing->getTelephone(),
            "postal_code" => $billing->getPostcode(),
            "region" => $billing->getRegionId(),
            "street_address" => $billing->getStreetLine(1)
        ];
        return $request_customer;
    }
    /**
     * Set Data to get card token 
     *  
     * @param $payment
     * @return $requestParams
     */
    public function setCardTokenData($payment = null) {       
        if ((strlen($payment->getCcExpMonth())) < 2) {
            $exp_month = '0' . $payment->getCcExpMonth();
        } else {
            $exp_month = $payment->getCcExpMonth();
        }
        return $request_card = [
            "card_number" => $payment->getCcNumber(),
            "cvv" => $payment->getCcCid(),
            "expiry_month" => $exp_month,
            "expiry_year" => substr($payment->getCcExpYear(), -2),
            "organisation" => $this->config->getPaymentConfigData('organization_id')
        ];
    }
    /**
     * Set Data to get card token 
     *  
     * @param $payment
     * @return $requestParams
     */
    
    public function getCardTokenData() {       
        $cardId = $this->_checkoutSession->getDimeboxCardId();
        return $cardId;
        
    }
    
}