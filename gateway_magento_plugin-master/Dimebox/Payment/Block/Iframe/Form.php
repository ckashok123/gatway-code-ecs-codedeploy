<?php

/**
 * 
 * Dime box I-frame block  
 * 
 * @category    Dime box
 * @package     Dime box
 * 
 */

namespace Dimebox\Payment\Block\Iframe;

/**
 * Block file to load payment form using I-frame
 * 
 * @param \Magento\Framework\View\Element\Template
 */
class Form extends \Magento\Framework\View\Element\Template {

    const JSPATH = 'static/jsclient/script.js';

    protected $scopeConfig;
    protected $messageManager;
    protected $checkoutSession;
    protected $_coreRegistry;

    /**
     * 
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Dimebox\Payment\Model\Config $serviceConfig
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context, 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, 
        \Dimebox\Payment\Model\Config $serviceConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Dimebox\Payment\Model\AdditionalConfigProvider $configProvider,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->config = $serviceConfig;
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->messageManager = $messageManager;
        $this->_coreRegistry = $coreRegistry;

        parent::__construct($context, $data);
    }

    /**
     * Function to get organization id to pass in payment form  
     * 
     * @field Null
     * 
     * @return organization Id
     */
    public function getOrganizationId() {
        $organizationId = $this->config->getPaymentConfigData('organization_id');
        return $organizationId;
    }

    /**
     * Function to get base url to display payment form  
     * 
     * @field Null
     * 
     * @return $baseUrl
     */
    public function getBaseUrl() {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        return $baseUrl;
    }

    /**
     * Function to get payment gateway URL to display payment form  
     * 
     * @field Null
     * 
     * @return $baseUrl
     */
    public function getPaymentGatwayUrl() {
        $gatwayUrl = $this->config->getPaymentConfigData('gateway_url');
        return $gatwayUrl;
    }

    public function getEnvironmentUrl() {
        $jspath = self::JSPATH;
        $environmentUrl = $this->_scopeConfig->getValue('payment/dimebox_payment/gateway_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $environmentUrl = str_replace('v1', '', $environmentUrl) . $jspath;
        return $environmentUrl;
    }

    public function getPareq() {

        return $this->_coreRegistry->registry('pareq');
    }

    public function getActionUrl() {

        return $this->_coreRegistry->registry('actionUrl');
    }
	
	 public function getStatus() {

        return $this->_coreRegistry->registry('status');
    }

}
