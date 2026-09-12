<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $connection = $setup->getConnection();
        if (version_compare($context->getVersion(), '2.1.0', '<')) {
            $tableName = $setup->getTable('magefan_blog_post');
            $connection->addColumn(
                $tableName,
                'rp_conditions_serialized',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => '2M',
                    'comment' => 'Related Products Rules (Blog+)',
                ]
            );
            $connection->addColumn(
                $tableName,
                'rp_conditions_generation_time',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'comment' => 'Related Products Rules Last Run (Blog+)',
                ]
            );

            $tableName = $setup->getTable('magefan_blog_post_relatedproduct');
            $connection->addColumn(
                $tableName,
                'related_by_rule',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'length' => 1,
                    'nullable' => true,
                    'comment' => 'Is Generate By Related Product Rule (Blog+)',
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.9.1', '<')) {
            $table = $setup->getTable('magefan_blog_post');

            $connection->addColumn(
                $table,
                'enable_comments',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'length' => null,
                    'nullable' => false,
                    'default' => '1',
                    'comment' => 'Enable Comments (Blog+)',
                ]
            );

            $connection->addColumn(
                $setup->getTable('magefan_blog_post'),
                'featured_list_img',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Thumbnail Image (Blog+)',
                    'after' => 'featured_img_alt'
                ]
            );
            $connection->addColumn(
                $setup->getTable($table),
                'featured_list_img_alt',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Featured List Image Alt (Blog+)',
                    'after' => 'featured_list_img'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.10.13', '<')) {
            $table = $installer->getTable('magefan_blog_product_tmp');

            //Delete cause error: SQLSTATE[HY000]: General error -> InnoDB presently supports one FULLTEXT index creation at a time
            $connection->dropIndex($table, $setup->getIdxName(
                $setup->getTable('magefan_blog_product_tmp'),
                ['title'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ));
            $connection->dropIndex($table, $setup->getIdxName(
                $setup->getTable('magefan_blog_product_tmp'),
                ['description'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ));
            $connection->dropIndex($table, $setup->getIdxName(
                $setup->getTable('magefan_blog_product_tmp'),
                ['short_description'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ));

            $connection->addColumn(
                $table,
                'id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'comment' => 'Primary key'
                ]
            );

            $connection->addIndex(
                $setup->getTable('magefan_blog_product_tmp'),
                $setup->getIdxName(
                    $setup->getTable('magefan_blog_product_tmp'),
                    ['title'],
                    AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                ['title'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            );

            $connection->addIndex(
                $setup->getTable('magefan_blog_product_tmp'),
                $setup->getIdxName(
                    $setup->getTable('magefan_blog_product_tmp'),
                    ['description'],
                    AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                ['description'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            );

            $connection->addIndex(
                $setup->getTable('magefan_blog_product_tmp'),
                $setup->getIdxName(
                    $setup->getTable('magefan_blog_product_tmp'),
                    ['short_description'],
                    AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                ['short_description'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            );
        }

        $installer->endSetup();
    }
}
