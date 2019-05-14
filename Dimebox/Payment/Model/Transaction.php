<?php

/**
 * Dime box transaction API Model
 *
 */

namespace Dimebox\Payment\Model;

use Dimebox\Payment\Api\TransactionInterface;
use Dimebox\Payment\Model\Settlements;
class Transaction implements TransactionInterface {
	
	protected $_canVoid = true;
	protected $_checkoutSession;
    protected $_request;
    protected $settlements;
    
	protected $_orderRepository;
    protected $_invoiceService;
    protected $_transaction;
	
	const CODE = 'dimebox_payment';
    const CCCAPTURE = 'ccCapture';
    const AUTHORIZATION = 'authorization';
    const AUTHANDCAPTURE = 'authAndCapture';

    /**
     * 
     * @param \Psr\Log\LoggerInterface $logs
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Dimebox\Payment\Model\Config $config
     * @param \Dimebox\Payment\Model\Core\ServiceConfig $serviceConfig
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logs,
        \Dimebox\Payment\Model\Config $config,
        \Dimebox\Payment\Model\Core\ServiceConfig $serviceConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Sales\Model\Order\Payment\Transaction $order,
		\Dimebox\Payment\Model\Extension $extension,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
		\Magento\Checkout\Model\Session $checkoutSession,
         settlements $settlements
    ) {
        $this->_logs = $logs;
        $this->config = $config;
        $this->serviceConfig = $serviceConfig;
        $this->resourceConnection = $resourceConnection;
        $this->_request = $request;
        $this->_storeManager = $storeManager;
		$this->order = $order;
		$this->_extension = $extension;
		$this->_orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
		$this->_checkoutSession = $checkoutSession;
        $this->settlements = $settlements;  
    }

    /**
     * Returns update message to user
     *
     * @api
     * @param string $id Transaction id.
     * @return string Greeting message with users name.
     */
    public function update($id = null,$payment = null, $amount =  null) {
        $trasactionId = $this->_request->getParam('id');
        $transUrl = $this->config->getPaymentConfigData('gateway_url') . '/' . $this->config->getPaymentConfigData('transaction') . '/' . $trasactionId;
        $responseGetTrans = $this->serviceConfig->getCurlDataTags($transUrl);
        $status = $responseGetTrans->status;
		
		switch ($status) {
			case 'SETTLEMENT_REQUESTED':
				$paymentStatus = 'capture';
				break;
			case 'AUTHORIZED':
				$paymentStatus = 'authorization';
				break;
			case 'AUTHORIZATION_VOIDED':
				$paymentStatus = 'void';
				break;
			case 'SETTLEMENT_CANCELLED':
				$paymentStatus = 'void';
				break;
		}
		
        $collection = $this->settlements->getCollection()->addFieldToFilter('transaction_id',$trasactionId);
        foreach($collection as $data){
            $reportId = $data->getReportId(); 
            $settlementData = $this->settlements->load($reportId);
            $settlementData->setSettlementStatus($status); 
            $settlementData->save(); 			
        }
		
		$orderCollection = $this->order->getCollection()->addFieldToFilter('txn_id',$trasactionId);
		foreach($orderCollection as $orderData){
            $transId = $orderData->getTransactionId(); 
            $this->order->load($transId)->setTxnType($paymentStatus)->save();
			if($paymentStatus == 'capture'){
                            $orderId = $orderData->getOrderId();
                            $order = $this->_orderRepository->get($orderId);
                            if($order->canInvoice()) {
                                    $invoice = $this->_invoiceService->prepareInvoice($order);
                                    $invoice->register();
                                    $invoice->save();
                                    $transactionSave = $this->_transaction->addObject(
                                            $invoice
                                    )->addObject(
                                            $invoice->getOrder()
                                    );
                                    $transactionSave->save();
                            }
                        }
			
			 return $responseGetTrans->status;
		
		 
		 
		}

        
}

}

?>



?>