<?php

/**
 * Dime box Install schema  
 *
 * @package     Dime box
 * @category    Dime box 
 * 
 */

namespace Dimebox\Payment\Setup;

/**
 * Install schema creation related to dime box payment module
 *
 */
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface {

    /**
     * 
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();
        $installer->endSetup();
    }

}

//ends install schema interface 