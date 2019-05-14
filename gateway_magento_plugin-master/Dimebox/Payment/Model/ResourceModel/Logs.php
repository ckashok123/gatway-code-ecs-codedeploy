<?php

/**
* Logs Resource Model
*/

namespace Dimebox\Payment\Model\ResourceModel;
class Logs extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

/**
* Initialize resource model
*
* @return void
*/
    protected function _construct()
    {
        $this->_init('dimebox_payment_logs', 'log_id');
    }
}

