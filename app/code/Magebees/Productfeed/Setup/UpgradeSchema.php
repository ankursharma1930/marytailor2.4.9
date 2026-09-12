<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{


	public function upgrade( SchemaSetupInterface $setup,ModuleContextInterface $context) {
        $installer = $setup;

        $installer->startSetup();
        if (version_compare($context->getVersion(), "1.0.0", "<")) {
        //Your upgrade script
        }
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
          $installer->getConnection()->addColumn(
                $installer->getTable('magebees_productfeed_feed'),
                'ftp_settings',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '2M',
                    'nullable' => true,
                    'comment' => 'Ftp Setttings'
                ]
            );
        }
        $installer->endSetup();
    }
}
