<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
 
        $connection = $installer->getConnection();
 
        $connection->dropTable($connection->getTableName('magebees_productfeed_items'));
        $connection->dropTable($connection->getTableName('magebees_productfeed_templates'));
        $connection->dropTable($connection->getTableName('magebees_productfeed_mapping'));
        $connection->dropTable($connection->getTableName('magebees_productfeed_dynamicattributes'));
        $connection->dropTable($connection->getTableName('magebees_productfeed_feed'));
        $installer->endSetup();
    }
}
