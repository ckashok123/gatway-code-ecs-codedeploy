<?php

/**
 * Settlements Resource Collection
 */

namespace Dimebox\Payment\Model\ResourceModel\Settlements;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection 
{

    /**
     *
     * @fieldname report_id
     */
    protected $_idFieldName = 'report_id';

    /*
     * Field Null
     */

    protected function _construct() {
        $this->_init(
                'Dimebox\Payment\Model\Settlements', 'Dimebox\Payment\Model\ResourceModel\Settlements'
        );
    }

}
