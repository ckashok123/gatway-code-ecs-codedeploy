<?php

namespace Dimebox\Payment\Model\Core;

/**
 * Class Connection
 *
 * Connection assemble the Curl/API call request and
 * parsing the returned response
 *
 * @package Dimebox\Connection
 */
class Connection extends \Magento\Payment\Model\Method\AbstractMethod {

    protected $_Config;

    /**
     * curl instance
     */
    private $ch = null;

    /**
     * header information
     */
    private $headers = array();

    /**
     * Contains all request Options [CURLOPT]
     */
    private $curlOptions = array();

    /**
     * Curl response
     */
    private $response = [];

    /**
     * SSL enabled flag
     */
    public $isSSLCert = false;
    protected $_logs;

    /**
     * Default Constructor
     */
    public function __construct(
    \Magento\Framework\Model\Context $context, \Dimebox\Payment\Model\Config $serviceConfig, \Psr\Log\LoggerInterface $logs
    ) {
        $this->_logs = $logs;
        $this->config = $serviceConfig;
    }

    /**
     * Initiate curl request and validate & set request URL.
     *       
     * @param [string] $url  Target URL
     * @return Object  $this
     */
    public function setRequestUrl($url = null) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid API endpoint URL - ' . $url);
        }
        $this->ch = curl_init($url);

        return $this;
    }

    /**
     * Some default options for curl
     * These are typically overridden by user defined config
     */
    private function useDefaultCurlOptions() {
        $this->curlOptions[CURLOPT_FOLLOWLOCATION] = 0;
        $this->curlOptions[CURLOPT_RETURNTRANSFER] = 1;

        if (!$this->isSSLCert) {
            $this->curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
            $this->curlOptions[CURLOPT_SSL_VERIFYPEER] = 0;
        }
    }

    /**
     * Set request http method.
     * 
     * @param  string  $method  Http method
     * @return Object  $this
     */
    public function setHttpMethod($method = 'get') {
        $method = strtolower((string) $method);

        if (!in_array($method, ['post', 'get', 'put', "delete", 'head', 'options', 'connect'])) {

            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid HTTP method - ' . $method));
        }
        $this->curlOptions[CURLOPT_CUSTOMREQUEST] = $method;

        return $this;
    }

    /**
     * Set request data to be send to request URL.
     * 
     * @param  mixed $post_data  Request data to be send to request URL.
     * @return $this
     */
    public function setPostFields($post_data = null) {
        $xapi = $this->config->getPaymentConfigData('x_apikey');
        $this->curlOptions[CURLOPT_HTTPHEADER] = array(
            "content-type: application/json",
            "X-APIKEY: $xapi");
        $this->curlOptions[CURLOPT_POSTFIELDS] = json_encode($post_data, true);
        return $this;
    }

    /**
     * Set ssl cert path for certificate based client authentication
     *
     * @param string  $certPath   SSL certificate path
     */
    public function setSSLCertPath($certPath) {
        if (empty($certPath)) {

            throw new \Magento\Framework\Exception\LocalizedException(__("Please provide a valid SSL cert path"));
        }
        $this->curlOptions[CURLOPT_SSLCERT] = realpath($certPath);

        return $this;
    }

    /**
     * Set ssl cert passPhrase for certificate based client authentication
     *
     * @param null    $passPhrase  
     */
    public function setSSLCertPasswd($passPhrase = null) {
        if (isset($passPhrase) && trim($passPhrase) != "") {
            $this->curlOptions[CURLOPT_SSLCERTPASSWD] = $passPhrase;
        }

        return $this;
    }

    /**
     * Execute/send request.
     * 
     * @param none
     * @return bool|mixed
     */
    public function execute() {
        $this->useDefaultCurlOptions();

        curl_setopt_array($this->ch, $this->curlOptions);

        ## curl execute
        $this->response['curl_response'] = curl_exec($this->ch);
        $this->response['curl_errno'] = curl_errno($this->ch);
        $this->response['curl_error'] = curl_error($this->ch);
        $this->response['curl_getinfo'] = curl_getinfo($this->ch);
        $this->close();
        return $this->response;
    }

    /**
     * @purpose : This is used to close curl request and reset all properties.
     */
    private function close() {
        curl_close($this->ch);

        $this->ch = null;
        $this->curlOptions = array();
        $this->headers = array();
    }

}

## end Connection class
