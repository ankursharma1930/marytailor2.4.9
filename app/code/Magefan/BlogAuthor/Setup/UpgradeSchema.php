<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\BlogAuthor\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Blog schema update
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $setup->startSetup();

        $version = $context->getVersion();
        $connection = $setup->getConnection();

        if (version_compare($version, '2.10.0') < 0) {
            foreach (['magefan_blog_author'] as $table) {
                $connection->addColumn(
                    $setup->getTable($table),
                    'posts_per_page',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Posts Per Page',

                    ]
                );
                $connection->addColumn(
                    $setup->getTable($table),
                    'posts_list_template',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 100,
                        'nullable' => true,
                        'comment' => 'Posts List Template',
                    ]
                );
            }
        }

        if (version_compare($version, '2.10.10') < 0) {
            /**
             * Create table 'magefan_blog_author_store'
             */
            $table = $connection->newTable(
                $setup->getTable('magefan_blog_author_store')
            )->addColumn(
                'author_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'primary' => true],
                'Author ID'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Store ID'
            )->addIndex(
                $setup->getIdxName('magefan_blog_author_store', ['store_id']),
                ['store_id']
            )->addForeignKey(
                $setup->getFkName('magefan_blog_author_store', 'author_id', 'magefan_blog_author', 'author_id'),
                'author_id',
                $setup->getTable('magefan_blog_author'),
                'author_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName('magefan_blog_author_store', 'store_id', 'store', 'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Magefan Blog Author To Store Linkage Table'
            );
            $connection->createTable($table);
        }

        if (version_compare($version, '2.11.1') < 0) {
            /**
             * Create table 'magefan_blog_post_coauthor'
             */
            $table = $connection->newTable(
                $setup->getTable('magefan_blog_post_coauthor')
            )->addColumn(
                'post_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'primary' => true],
                'Post ID'
            )->addColumn(
                'coauthor_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'primary' => true],
                'Coauthor ID'
            )->addIndex(
                $setup->getIdxName('magefan_blog_post', ['post_id']),
                ['post_id']
            )->addForeignKey(
                $setup->getFkName('magefan_blog_post_coauthor', 'post_id', 'magefan_blog_post', 'post_id'),
                'post_id',
                $setup->getTable('magefan_blog_post'),
                'post_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName('magefan_blog_post_coauthor', 'coauthor_id', 'magefan_blog_author', 'author_id'),
                'coauthor_id',
                $setup->getTable('magefan_blog_author'),
                'author_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Magefan Blog Posts To Co-Author Table'
            );
            $connection->createTable($table);
        }

        if (version_compare($version, '2.11.3') < 0) {
            if ($connection->isTableExists($setup->getTable('magefan_blog_author'))) {
                $connection->addIndex(
                    $setup->getTable('magefan_blog_author'),
                    $setup->getIdxName(
                        $setup->getTable('magefan_blog_author'),
                        ['is_active']
                    ),
                    ['is_active']
                );
            }
        }

        $setup->endSetup();
    }
}
