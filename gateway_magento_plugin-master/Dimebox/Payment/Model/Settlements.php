<?php
/**
 * Dime box Settlement Report Model
 *
 * @category Dime box
 * @package Dime box
 */

namespace Dimebox\Payment\Model;

class Settlements extends \Magento\Framework\Model\AbstractModel {

    /**
     * @field Null
     * @return void
     */
    protected function _construct() {
        $this->_init('Dimebox\Payment\Model\ResourceModel\Settlements');
    }

}
