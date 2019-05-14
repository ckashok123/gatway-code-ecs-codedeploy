<?php

/**
 * Dime box helper file
 */

namespace Dimebox\Payment\Helper;

use Magento\Framework\App\Action\Context;

/**
 * 
 * Dime box helper class to create common function
 * 
 * */
class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    /**
     * @param Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * */
    public function __construct(
    Context $context, \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
    }

     /**
     * Insert log details in database  
     *  
     * @param $data
     * @return void
     * */
    public function insertLog($data) {
        // insert error log	
            $model = $this->_objectManager->create('Dimebox\Payment\Model\Logs');
            $model->setData($data);
            $model->save();

    }

}
