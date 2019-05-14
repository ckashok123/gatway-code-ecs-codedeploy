<?php

/**
* Settlements Resource Model
*/

namespace Dimebox\Payment\Model\ResourceModel;
class Settlements extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

/**
* Initialize resource model
*
* @return void
*/
    protected function _construct()
    {
		$this->_init('dimebox_settlement_report', 'report_id');
    }
}

