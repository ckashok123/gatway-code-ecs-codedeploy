<?php

/**
 * Dime box observer to checkout one page success event
 * 
 * @category   Dime box
 * @package    Dime box  
 */

namespace Dimebox\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Dimebox\Payment\Model\Settlements;
class Report implements ObserverInterface {

    /**
     *
     * @var type $Settlements
     */
       protected $settlements;
       
       
       
    /**
     * 
     * @param \Psr\Log\LoggerInterface $logs
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Dimebox\Payment\Model\Config $config
     * @param \Dimebox\Payment\Model\Core\ServiceConfig $serviceConfig
     */
    public function __construct(
    \Psr\Log\LoggerInterface $logs,
     \Magento\Framework\App\ResourceConnection $resourceConnection, \Dimebox\Payment\Model\Config $config, \Dimebox\Payment\Model\Core\ServiceConfig $serviceConfig, settlements $settlements) {
        $this->_logs = $logs;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
        $this->serviceConfig = $serviceConfig;
        $this->settlements = $settlements;  
        
    }

    /**
     * 
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {

		$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
		$checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');
		if($checkoutSession->getDimeboxCardId()){
			$checkoutSession->unsDimeboxCardId();
		}
        $orderId = $observer->getEvent()->getOrderIds();
        $id = $orderId[0];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->load($id);
        $transId = $order->getPayment()->getLastTransId();
        $method = $order->getPayment()->getMethodInstance();
        $methodTitle = $method->getTitle();
        $transUrl = $this->config->getPaymentConfigData('gateway_url') . '/' . $this->config->getPaymentConfigData('transaction') . '/' . $transId; 
		$status = '';
		$responseGetTrans = $this->serviceConfig->getCurlDataTags($transUrl);
			if (!empty($responseGetTrans)) {
				$status = $responseGetTrans->status;
			}
        $settlementData = $this->settlements;
        $settlementData->setTransactionId($transId);
        $settlementData->setOrderId($id);
        $settlementData->setTransactionType($methodTitle);
        $settlementData->setSettlementStatus($status);
        $settlementData->save();
        
    }

}
