<?php

namespace Dimebox\Payment\Model\Core;

/**
 * Class Processor
 *
 * Processor process the API Call.
 *
 * @package Dimebox
 */
class Processor extends \Magento\Payment\Model\Method\AbstractMethod {

    /**
     * ServiceConfig object
     */
    private $serviceConfig;

    /**
     * Request parameters
     */
    private $_requestParams = [];

    /**
     * API Request parameters
     */
    private $_requestMethod = null;
    protected $_logs;

    /**
     * Default Constructor
     */
    public function __construct(
    \Magento\Framework\Model\Context $context, \Psr\Log\LoggerInterface $logs
    ) {
        $this->_logs = $logs;
    }

    /**
     * Set default API config params
     * @param [type] $serviceConfig [description]
     */
    public function setServiceConfig($serviceConfig = null) {
        $this->serviceConfig = $serviceConfig;
    }

    /**
     * Set API request method.
     * 
     * @param  string  $requestMethod SDK/API method name
     * @return object  $this
     */
    public function setMethodName($requestMethod = null) {
        $this->_requestMethod = $requestMethod;

        return $this;
    }

    /**
     * Set and Check API request parameters.
     *
     * @param  array   $postData  Array holds gateway request parameters.
     * @return object  $this
     */
    public function setParams($postData = array()) {
        if (empty($postData) || !is_array($postData)) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Invalid API request parameters."));
        }
        $this->_requestParams = $postData;
        return $this;
    }

    /**
     * Identify and Invoking defined API method request
     * return exception otherwise.
     * 
     * @param object  $this->{$this->_requestMethod}()  API response
     */
    public function getResponse() {
        if (empty($this->_requestMethod) || !method_exists($this, $this->_requestMethod)) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Invalid API request method."));
        }
        $this->_requestParams += $this->serviceConfig->getConfig();
        return $this->{$this->_requestMethod}();
    }

    /**
     * Verify the card detail and address detail of customer
     */
    private function authenticate() {
        $this->_requestParams['request_action'] = 'TESTAUTH';
        $response = $this->serviceConfig->executeCall($this->_requestParams);
        return $this->serviceConfig->filterResponse($response, []);
    }

    /**
     * Confirm the availability of funds in the cardholder�s bank account.
     */
    private function authorization() {
        $this->_requestParams['request_action'] = 'CCAUTHORIZE';
        $response = $this->serviceConfig->executeCall($this->_requestParams);
        return $this->serviceConfig->filterResponse($response, []);
    }

    /**
     * Capture of the authorized funds in a single request to the Payment Service.
     * 
     * @field Null
     * return authandcaptureResponse
     */
    private function authAndCapture() {
        $this->_requestParams['request_action'] = 'CCAUTHCAP';
        $response = $this->serviceConfig->executeCall($this->_requestParams);
        return $this->serviceConfig->filterResponse($response, []);
    }

    /**
     * Void a previous authorization.
     * 
     * @field Null
     * return filterResponse 
     */
    private function ccReverse() {
        $this->_requestParams['request_action'] = 'CCREVERSE';
        $response = $this->serviceConfig->executeCall($this->_requestParams);
        return $this->serviceConfig->filterResponse($response, []);
    }

    /**
     * Capture a previous authorization
     * 
     * @field Null
     * return captureResponse
     */
    private function ccCapture() {

        $this->_requestParams['request_action'] = 'CCCAPTURE';
        $response = $this->serviceConfig->executeCall($this->_requestParams);
        return $this->serviceConfig->filterResponse($response, []);
    }

    /**
     * Refund a previously captured authorization
     */
    private function ccCredit() {
        $this->_requestParams['request_action'] = 'CCCREDIT';
        $response = $this->serviceConfig->executeCall($this->_requestParams);
        return $this->serviceConfig->filterResponse($response, []);
    }

    /**
     * Check the status of a previous purchase 
     * useful if the connection timed out, which creates uncertainty about the purchase result.
     * 
     */
    private function ccStatus() {
        $this->_requestParams['request_action'] = 'CCSTATUS';
        $response = $this->serviceConfig->executeCall($this->_requestParams);
        return $this->serviceConfig->filterResponse($response, []);
    }

}

## end Processor Class
