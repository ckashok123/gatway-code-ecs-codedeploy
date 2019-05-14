<?php

namespace Dimebox\Payment\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;

        $installer->startSetup();
        if (version_compare($context->getVersion(), '2.0.4', '<')) {
            if (!$installer->tableExists('dimebox_payment_logs')) {
                $table = $installer->getConnection()->newTable(
                                $installer->getTable('dimebox_payment_logs')
                        )
                        ->addColumn(
                                'log_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, [
                            'identity' => true,
                            'nullable' => false,
                            'primary' => true,
                            'unsigned' => true,
                            'auto_increment' => true
                                ], 'Log ID'
                        )
                        ->addColumn(
                                'reason_code', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 100, ['nullable => false'], 'Reason Code'
                        )
                        ->addColumn(
                                'code_detail', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 100, ['nullable => false'], 'Code Detail'
                        )
                        ->addColumn(
                                'detail_message', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, '64k', [], 'Message Detail'
                        )
                        ->addColumn(
                                'quote_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10, ['nullable => false'], 'Quote Id'
                        )->addColumn(
                                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT], 'Created At'
                        )
                        ->setComment('Logs Table');
                $installer->getConnection()->createTable($table);
            }
            if (!$installer->tableExists('dimebox_settlement_report')) {
                $table_report = $installer->getConnection()->newTable(
                                $installer->getTable('dimebox_settlement_report')
                        )->addColumn(
                                'report_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true], 'Report Id'
                        )->addColumn(
                                'transaction_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 127, [], 'Transaction Id'
                        )->addColumn(
                                'order_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 127, [], 'Order Id'
                        )->addColumn(
                                'transaction_type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 127, [], 'Transaction Type'
                        )->addColumn(
                                'settlement_status', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 127, [], 'Settlement Status'
                        )->addColumn(
                        'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT], 'Created At'
                );
                $installer->getConnection()->createTable($table_report);
                $installer->endSetup();
            }
        }

        
    }

}
