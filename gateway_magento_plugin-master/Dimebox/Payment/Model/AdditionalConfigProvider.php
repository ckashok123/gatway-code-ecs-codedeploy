<?php

namespace Dimebox\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class AdditionalConfigProvider implements ConfigProviderInterface {

    protected $_scopeConfig;

    const JSPATH = 'static/jsclient/script.js';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context, 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, 
        \Magento\Store\Model\StoreManagerInterface $storeManager, 
        \Magento\Checkout\Model\Session $checkoutSession,
         Config $config,
         \Psr\Log\LoggerInterface $logs,
         array $data = []
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->_logs = $logs;
    }

    public function getConfig() {
        $config = array();
        $config['environmentUrl'] = $this->getEnvironmentUrl();
        $config['baseUrl'] = $this->getBaseUrl();
        $config['pareq'] = $this->_checkoutSession->getPareqSession();
        $config['secureCard'] = $this->getSecureEnableStatus();
        $config['actionUrl'] = $this->_checkoutSession->getActionUrlSession();
        $config['status'] = $this->_checkoutSession->getEnrolmentStatusSession();
        
        return $config;
    }

    public function getBaseUrl() {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        
        return $baseUrl;
    }

    public function getSecureEnableStatus() {
        $secureCardStatus = $this->config->getPaymentConfigData('3denroll_cofiguration');
        $this->_logs->log(\Psr\Log\LogLevel::INFO, print_r($secureCardStatus, true));
        
        return $secureCardStatus;
    }

    public function getEnvironmentUrl() {
        $jspath = self::JSPATH;
        $environmentUrl = $this->_scopeConfig->getValue('payment/dimebox_payment/gateway_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $environmentUrl = str_replace('v1', '', $environmentUrl) . $jspath;
        
        return $environmentUrl;
    }

}
