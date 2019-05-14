<?php

namespace Dimebox\Payment\Model\Core;

/**
 * Class ServiceConfig
 *
 * ServiceConfig loads the SDK configuration file and
 * hands out appropriate config params to other classes
 *
 * @package Dimebox
 */
class ServiceConfig {

    /**
     * API endpoint
     */
    private $apiEndpoint;
    private $curl = null;
    protected $_logs;
    protected $curlTemp;
    protected $_logFactory;
    protected $_objectManager;
    protected $_checkoutSession;
    protected $helper;

    /**
     * Default Config parameters.
     */
    private $_requestParams = [
        'request_response_format' => 'json',
        'request_api_version' => 3.6
    ];

    /**
     * Required Configuration parameters. Add more required params for Dimebox(chetu)
     */
    private $_requiredParams = ['end_point'];

    /**
     * 
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\HTTP\Client\Curl $curlTemp
     * @param \Dimebox\Payment\Model\LogsFactory $logFactory
     * @param \Psr\Log\LoggerInterface $logs
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Dimebox\Payment\Model\Config $serviceConfig
     * @param \Dimebox\Payment\Helper\Data $paymentData
     */
    public function __construct(
    \Magento\Framework\Model\Context $context, \Magento\Framework\HTTP\Client\Curl $curlTemp, \Dimebox\Payment\Model\LogsFactory $logFactory, \Psr\Log\LoggerInterface $logs, \Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Checkout\Model\Session $checkoutSession, \Dimebox\Payment\Model\Config $serviceConfig, \Dimebox\Payment\Helper\Data $paymentData
    ) {
        $this->_logs = $logs;
        $this->curlTemp = $curlTemp;
        $this->_logFactory = $logFactory;
        $this->_objectManager = $objectManager;
        $this->_checkoutSession = $checkoutSession;
        $this->config = $serviceConfig;
        $this->_paymentData = $paymentData;
    }

    public function serviceConfig($conf = array(), $Connection = null) {
        $this->curl = $Connection;
        foreach ($this->_requiredParams as $param) {
            if (empty($conf[$param])) {

                $this->_logs->log(
                        \Psr\Log\LogLevel::INFO, "Error in payment processing request, please contact to your service provider."
                );
                throw new \Magento\Framework\Exception\LocalizedException(__("Error in payment processing request, please contact to your service provider."));
            }
        }
        $this->apiEndpoint = $conf['end_point'];

        if (isset($conf['ssl_cert_path'])) { // check if SSL enable on server
            $this->curl->isSSLCert = true;
            $this->curl->setSSLCertPath($conf['ssl_cert_path']);
            $this->curl->setSSLCertPasswd($conf['ssl_cert_passphrase']);

            unset($conf['ssl_cert_path'], $conf['ssl_cert_passphrase']);
        }
        $this->_requestParams = $conf + $this->_requestParams;
        unset($this->_requestParams['end_point']); //remove url from params list
    }

    /**
     * Get request parameters
     */
    public function getConfig() {
        return $this->_requestParams;
    }

    /**
     * Convenience method for making POST requests.
     * 
     * @param  array  $postData    Array holds gateway request parameters.
     * @return array  $response    Gateway response for both success or failure.
     */
    public function executeCall(array $postData) {
        $response = $this->curl
                ->setRequestUrl($this->apiEndpoint)
                ->setPostFields($postData)
                ->execute();
        return $this->handleResponse($response);
    }

    /**
     * [handleResponse description]
     * 
     * @param  [type] $response [description]
     * @return [type]           [description]
     */
    public function handleResponse($response) {
        // handle response description
        $quoteId = $this->_checkoutSession->getQuoteId();
        $error_desc = 'cURL ERROR -> ' . $response['curl_error'] . ': ' . $response['curl_error'];
        $returnCode = (int) $response['curl_getinfo']['http_code'];

        if (!empty($response['curl_error']) || $response['curl_errno'] > 0 || $returnCode != 200) {
            $response['error_desc'] = $error_desc;
            $parseResult = json_decode($response['curl_response']);
            $data['reason_code'] = $parseResult->code;
            $encodeCode = json_encode($parseResult->details);
            $data['code_detail'] = $encodeCode;
            $data['detail_message'] = $parseResult->message;
            $data['quote_id'] = $quoteId;
         
                $errorCodes = array(400, 401, 403, 404, 500, 501, 503);
                if(in_array($returnCode, $errorCodes)) {
                    $this->_paymentData->insertLog($data);
                    throw new \Magento\Framework\Exception\LocalizedException(__($parseResult->message));
                }
        }
        return $response['curl_response'];
    }

    /**
     * Filter API response by removing unnecessary properties.
     * 
     * @param  array $response     API response
     * @param  array $extraParams  unnecessary property list
     * @return array $response     filtered response
     */
    public function filterResponse($response, $extraParams = array()) {
        if (!empty($extraParams)) {
            foreach ($extraParams as $param) {
                unset($response[$param]);
            }
        }
        return $response;
    }

    /**
     * To encode array into URL encoded string for API request.
     * 
     * @param  array  $requestData  request post data
     * @return string request post data
     */
    public function toUrlEncode(array $requestData) {
        $urlEncodedString = '';

        foreach ($requestData as $key => $value) {
            $urlEncodedString .= $key . '=' . $value . '&';
        }

        return rtrim($urlEncodedString, '&');
    }

    /**
     * To pass post data and API URL for API request.
     * 
     * @param  array  $requestData  request post data
     * @return string request post data
     */
    public function getSessionTags($postData, $sessionTagApiUrl) {
        $xapi = $this->config->getPaymentConfigData('x_apikey');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $sessionTagApiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json",
                "X-APIKEY: $xapi"
            )
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    /**
     * To get data form web hook URL for API request.
     * 
     * @param  array  $requestData  request post data
     * @return string request post data
     */
    public function getCurlDataTags($sessionTagApiUrl) {
        $xapi = $this->config->getPaymentConfigData('x_apikey');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $sessionTagApiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json",
                "X-APIKEY: $xapi"
            )
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $parseResult = json_decode($response);
    }

}

## end Class ServiceConfig
