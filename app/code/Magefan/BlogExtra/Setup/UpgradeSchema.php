<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class UpgradeSchema
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;

        $installer->startSetup();

        $version = $context->getVersion();
        $connection = $setup->getConnection();
        $postCategoryTable = $setup->getTable('magefan_blog_post_category');

        if (version_compare($version, '2.9.0') < 0) {
            // Check if the table already exists
            if ($connection->isTableExists($postCategoryTable) == true) {
                $connection->addColumn(
                    $postCategoryTable,
                    'position',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Position',
                        'after' => 'category_id'
                    ]
                );
            }
        }

        if (version_compare($version, '2.9.1') < 0) {
            /**
             * Create table 'magefan_blog_comment_subscriber'
             */
            $table = $installer->getConnection()
                ->newTable($installer->getTable('magefan_blog_comment_subscriber'))
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'nullable' => false,
                            'primary' => true
                        ],
                        'ID'
                    )
                    ->addColumn(
                        'post_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false,
                            'primary' => true
                        ],
                        'Post ID'
                    )
                    ->addColumn(
                        'email',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => false],
                        'Email'
                    )
                    ->addColumn(
                        'status',
                        Table::TYPE_SMALLINT,
                        null,
                        [
                            'nullable' => false,
                            'default' => '1',
                        ],
                        'Status'
                    )
                    ->addForeignKey(
                        $installer->getFkName('magefan_blog_comment_subscriber', 'post_id', 'magefan_blog_post', 'post_id'),
                        'post_id',
                        $installer->getTable('magefan_blog_post'),
                        'post_id',
                        Table::ACTION_CASCADE
                    )
                    ->setComment("Magefan Blog Comment Subscribtions Table");

            $installer->getConnection()->createTable($table);

            $installer->getConnection()->addIndex(
                $installer->getTable('magefan_blog_comment_subscriber'),
                $setup->getIdxName(
                    $installer->getTable('magefan_blog_comment_subscriber'),
                    ['email'],
                    AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                ['email'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            );

            // Check if the table already exists
            if ($connection->isTableExists($setup->getTable('magefan_blog_comment')) == true) {
                $connection->addColumn(
                    $setup->getTable('magefan_blog_comment'),
                    'notification',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'length' => null,
                        'nullable' => false,
                        'default' => '0',
                        'comment' => 'Notification',
                        'after' => 'status'
                    ]
                );

                $connection->addColumn(
                    $setup->getTable('magefan_blog_comment'),
                    'notification_sent',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'length' => null,
                        'nullable' => false,
                        'default' => '1',
                        'comment' => 'Notification Sent',
                        'after' => 'notification'
                    ]
                );

                $connection->addColumn(
                    $setup->getTable('magefan_blog_comment'),
                    'store_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'length' => null,
                        'nullable' => false,
                        'unsigned' => true,
                        'comment' => 'Store ID',
                        'after' => 'admin_id'
                    ]
                );

                $setup->getConnection()->addForeignKey(
                    $setup->getFkName('magefan_blog_comment', 'store_id', 'store', 'store_id'),
                    $setup->getTable('magefan_blog_comment'),
                    'store_id',
                    $setup->getTable('store'),
                    'store_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                );
            }
        }

        $blogPostTable = $setup->getTable('magefan_blog_post');

        if (version_compare($version, '2.10.9') < 0) {
            // Check if the table already exists
            if ($connection->isTableExists($blogPostTable) === true) {
                $connection->addColumn(
                    $blogPostTable,
                    'end_time',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        'length' => null,
                        [],
                        'comment' => 'Post End Time',
                    ]
                );
            }
        }

        if (version_compare($version, '2.11.3') < 0) {
            if ($connection->isTableExists($setup->getTable('magefan_blog_post'))) {
                $connection->addIndex(
                    $setup->getTable('magefan_blog_post'),
                    $setup->getIdxName(
                        $setup->getTable('magefan_blog_post'),
                        ['end_time']
                    ),
                    ['end_time']
                );
            }
        }

        $installer->endSetup();
    }
}
