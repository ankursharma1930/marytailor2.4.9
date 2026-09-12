<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Blog Plus setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();

        $connection->addColumn(
            $setup->getTable('magefan_blog_post'),
            'fb_auto_publish',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'length' => 1,
                ['nullable' => true, 'default' => 1],
                'comment' => 'Facebook Auto publish',
            ]
        );
        $connection->addColumn(
            $setup->getTable('magefan_blog_post'),
            'fb_post_format',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 1,
                ['nullable' => false],
                'comment' => 'Facebook Post Format',
            ]
        );
        $connection->addColumn(
            $setup->getTable('magefan_blog_post'),
            'fb_published',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'length' => 1,
                ['nullable' => true, 'default' => 0],
                'comment' => 'Is published to Facebook',
            ]
        );

        $connection->addIndex(
            $setup->getTable('magefan_blog_post'),
            $setup->getIdxName(
                $setup->getTable('magefan_blog_post'),
                ['title'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['title'],
            AdapterInterface::INDEX_TYPE_FULLTEXT
        );

        $connection->addIndex(
            $setup->getTable('magefan_blog_post'),
            $setup->getIdxName(
                $setup->getTable('magefan_blog_post'),
                ['content'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['content'],
            AdapterInterface::INDEX_TYPE_FULLTEXT
        );

        $connection->addColumn(
            $setup->getTable('magefan_blog_post_relatedpost'),
            'auto_related',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'length' => 1,
                'nullable' => true,
                'comment' => 'Auto Related',
            ]
        );

        $connection->addColumn(
            $setup->getTable('magefan_blog_post_relatedproduct'),
            'auto_related',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'length' => 1,
                'nullable' => true,
                'comment' => 'Auto Related',
            ]
        );

        $connection->addColumn(
            $setup->getTable('magefan_blog_post_relatedproduct'),
            'display_on_product',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'length' => 1,
                ['nullable' => true, 'default' => 0],
                'comment' => 'Display Post On Product Page',
            ]
        );

        $connection->addColumn(
            $setup->getTable('magefan_blog_post_relatedproduct'),
            'display_on_post',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'length' => 1,
                ['nullable' => true, 'default' => 0],
                'comment' => 'Display Product On Post Page',
            ]
        );

        $table = $connection->newTable(
            $setup->getTable('magefan_blog_post_relatedpost_log')
        )->addColumn(
            'post_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Post ID'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            255,
            ['nullable' => true],
            'Created Related Post'
        )->addForeignKey(
            $setup->getFkName('magefan_blog_post_relatedpost_log', 'post_id', 'magefan_blog_post', 'post_id'),
            'post_id',
            $setup->getTable('magefan_blog_post'),
            'post_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Magefan Blog Post Related Log Table'
        );
        $setup->getConnection()->createTable($table);

        $table = $connection->newTable(
            $setup->getTable('magefan_blog_post_relatedproduct_log')
        )->addColumn(
            'post_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Post ID'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            255,
            ['nullable' => true],
            'Created Related Product'
        )->addForeignKey(
            $setup->getFkName('magefan_blog_post_relatedproduct_log', 'product_id', 'magefan_blog_post', 'post_id'),
            'post_id',
            $setup->getTable('magefan_blog_post'),
            'post_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Magefan Blog Product Related Log Table'
        );
        $setup->getConnection()->createTable($table);

        $table = $connection->newTable(
            $setup->getTable('magefan_blog_product_tmp')
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Product Id'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Store Id'
        )->addColumn(
            'title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Title'
        )->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Description'
        )->addColumn(
            'short_description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Short Description'
        )->setComment(
            'Magefan Blog Product Tmp Table'
        );

        $setup->getConnection()->createTable($table);

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

        /**
         * Create table 'magefan_blog_post_group'
         */
        $table = $connection
            ->newTable($setup->getTable('magefan_blog_post_group'))
            ->addColumn(
                'post_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'primary' => true],
                'Post ID'
            )
            ->addColumn(
                'group_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'primary' => true],
                'Group ID'
            )->addIndex(
                $setup->getIdxName('magefan_blog_post_group', ['group_id']),
                ['group_id']
            )->addForeignKey(
                $setup->getFkName('magefan_blog_post_group', 'post_id', 'magefan_blog_post', 'post_id'),
                'post_id',
                $setup->getTable('magefan_blog_post'),
                'post_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Catalog Rules To Posts Groups');

            $setup->getConnection()->createTable($table);

        /**
         * Create table 'magefan_blog_category_group'
         */
        $table = $connection->newTable(
            $setup->getTable('magefan_blog_category_group')
        )->addColumn(
            'category_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'primary' => true],
            'Category ID'
        )->addColumn(
            'group_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Group ID'
        )->addIndex(
            $setup->getIdxName('magefan_blog_category_group', ['group_id']),
            ['group_id']
        )->addForeignKey(
            $setup->getFkName('magefan_blog_category_group', 'category_id', 'magefan_blog_category', 'category_id'),
            'category_id',
            $setup->getTable('magefan_blog_category'),
            'category_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment('Catalog Rules To Category Groups');

            $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}
