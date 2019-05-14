<?php

namespace Dimebox\Payment\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use Dimebox\Payment\Model\Extension;

/**
 * Config file to retrieve data from Payment Copnfiguration
 *
 * @category    payment
 * @package     Dimebox_Payment
 * @author      Chetu India Team
 */
class Config {

    /**
     * @var string
     */
    protected $methodCode = Extension::CODE;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve information from payment configuration table
     *
     * @param string $field
     *
     * @return string
     */
    public function getPaymentConfigData($field) {
        $code = $this->methodCode;

        $path = 'payment/' . $code . '/' . $field;
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
     /**
     * Get customer IP address
     *
     * @field Null
     * @return $ip
     */

    public function getIpAddress() {
        $ip_object = \Magento\Framework\App\ObjectManager::getInstance();
        $obj = $ip_object->get('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
        $ip = $obj->getRemoteAddress();
        return $ip;
    }

}
