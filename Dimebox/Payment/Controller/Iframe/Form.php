<?php
/**
 * Dime box Payment Form Controller 
 * 
 * @package   Dime box
 * @category  Dime box
 */
namespace Dimebox\Payment\Controller\Iframe;
use Magento\Checkout\Model\Session as CheckoutSession;
/**
 * Dime box controller to load I-frame payment form
 */
class Form extends \Magento\Framework\App\Action\Action {
    /**
     * @var Pagefactory
     * @var checkoutsession
     * @var coresession
     */
    protected $_pageFactory;
    protected $_checkoutSession;
    protected $_coreSession;
    protected $_storeManager;
    protected $_coreRegistry;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
    \Magento\Framework\App\Action\Context $context, 
	\Magento\Framework\View\Result\PageFactory $pageFactory, 
	 CheckoutSession $checkoutSession, 
	\Psr\Log\LoggerInterface $logs, 
	\Magento\Framework\Session\SessionManagerInterface $coreSession, 
	\Dimebox\Payment\Model\Extension $extension,
	\Dimebox\Payment\Model\Config $config,
	\Magento\Store\Model\StoreManagerInterface $storeManager,
	\Magento\Framework\Registry $coreRegistry
	) {
        $this->_pageFactory = $pageFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_logs = $logs;
        $this->_coreSession = $coreSession;
		$this->_extension =  $extension;
		$this->_config = $config;
		$this->_storeManager = $storeManager;
		$this->_coreRegistry = $coreRegistry;
        return parent::__construct($context);
		}
    /**
     * 
     * Retrieve card token information and save token in session
     * 
     * @field Null
     * return Void
     * */
    public function execute() {
		$secureCardStatus = $this->_config->getPaymentConfigData('3denroll_cofiguration');
		$getPaymentSession = $this->_checkoutSession->getPaymentSession();
        $postValues  = $this->getRequest()->getPostValue(); 
		if(isset($postValues['card']) && $secureCardStatus == 1){
		  $this->_checkoutSession->setDimeboxCardId($postValues['card']); 
		  $amount = $this->_checkoutSession->getQuote()->getGrandTotal();	    
		  $responseEnrolment = $this->getEnorlmentResponse($postValues['card'],$amount);
		  $responseEnrolment = json_decode($responseEnrolment);
		  $this->_checkoutSession->setAuthIdSession($responseEnrolment->_id);
		  $this->_checkoutSession->setEnrolmentStatusSession($responseEnrolment->enrolment->status);
		  $pareq = $responseEnrolment->enrolment->pareq;
		  $this->_checkoutSession->setPareqSession($pareq);
		  $actionUrl = $responseEnrolment->authentication->url;
		  $this->_checkoutSession->setActionUrlSession($actionUrl);
		  $this->_checkoutSession->setDimeboxCardNumber($postValues['db_cc_number']);
		  $this->_checkoutSession->setDimeboxCardMonth($postValues['db_cc_month']);
		  $this->_checkoutSession->setDimeboxCardYear($postValues['db_cc_year']);
		  $this->_checkoutSession->setDimeboxCardCVV($postValues['db_cc_cvv']);
		  $this->_checkoutSession->setDimeboxCardType($postValues['db_cc_type']);
		 
		  $this->_coreRegistry->register('actionUrl', $actionUrl);
		  $this->_coreRegistry->register('pareq', $pareq);
		  $this->_coreRegistry->register('status', $responseEnrolment->enrolment->status);
			
		} else if(isset($postValues['card'])) {
            $this->_checkoutSession->setDimeboxCardId($postValues['card']);      
        }
		
		$resultPage = $this->_pageFactory->create();
        $block = $resultPage->getLayout()
                ->createBlock('Dimebox\Payment\Block\Iframe\Form')
                ->setTemplate('Dimebox_Payment::form.phtml')
                ->toHtml();
        $this->getResponse()->setBody($block);
      
    }
	
	
   /**
     * Set Data to set data for enrollment 
     *  
     * @param $payment
     * @return $requestParams
     */
     
	public function getEnorlmentResponse($cardToken = null, $amount = null) {
            $currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
			if ($currencyCode == "EUR" || $currencyCode == "USD") {
                    $amount = $amount * 100;
                }
            $request_enrolment = [
            "amount" => $amount,
            "card" => $cardToken,
            "currency_code" => $currencyCode,
            "description" => "3DS Enrolment check",
            "authenticator" => $this->_config->getPaymentConfigData('card_authenticator')
            ];
         
			$xapi = $this->_config->getPaymentConfigData('x_apikey');
			$curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $this->_config->getPaymentConfigData('gateway_url') . '/' . $this->_config->getPaymentConfigData('enroll'),
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
	
	
}