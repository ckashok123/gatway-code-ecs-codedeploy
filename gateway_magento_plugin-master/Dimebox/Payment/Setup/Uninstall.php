<?php

/**
 * Dime box Uninstall schema  
 *
 * @package     Dime box
 * @category    Dime box 
 * 
 */

namespace Dimebox\Payment\Setup;
 
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class Uninstall implements UninstallInterface
{
    /**
     * 
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $setup->endSetup();
    }
}
// ends uninstall schema