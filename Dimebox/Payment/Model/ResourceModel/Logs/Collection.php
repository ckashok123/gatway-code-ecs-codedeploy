<?php

/**
* Logs Resource Collection
*/

namespace Dimebox\Payment\Model\ResourceModel\Logs;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     *
     * @return void
     */
	protected $_idFieldName = 'log_id';
	
	protected function _construct()
    {
        $this->_init(
            'Dimebox\Payment\Model\Logs',
            'Dimebox\Payment\Model\ResourceModel\Logs'
        );
    }
}