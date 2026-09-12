<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Blog setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'magefan_blog_author'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('magefan_blog_author')
        )->addColumn(
            'author_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Author ID'
        )->addColumn(
            'is_active',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => '1'],
            'Is Author Active'
        )->addColumn(
            'firstname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author FirstName'
        )->addColumn(
            'lastname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author LastName'
        )->addColumn(
            'email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author Email'
        )->addColumn(
            'role',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author role (developer)'
        )->addColumn(
            'facebook_page_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author in Facebook'
        )->addColumn(
            'twitter_page_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author in Twitter'
        )->addColumn(
            'instagram_page_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author in Instagram'
        )->addColumn(
            'googleplus_page_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author in GooglePlus'
        )->addColumn(
            'linkedin_page_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author in LinkedIn'
        )->addColumn(
            'meta_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author Meta Title'
        )->addColumn(
            'meta_keywords',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Author Meta Keywords'
        )->addColumn(
            'meta_description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Author Meta Description'
        )->addColumn(
            'identifier',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            ['nullable' => true, 'default' => null],
            'Author String Identifier'
        )->addColumn(
            'content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Author Content'
        )->addColumn(
            'short_content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Author Short Content'
        )->addColumn(
            'creation_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [],
            'Author Creation Time'
        )->addColumn(
            'update_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [],
            'Author Modification Time'
        )->addColumn(
            'featured_img',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author Image'
        )->addColumn(
            'page_layout',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Author Layout'
        )->addColumn(
            'layout_update_xml',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Author Layout Update Content'
        )->addColumn(
            'custom_theme',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            ['nullable' => true],
            'Author Custom Thema'
        )->addColumn(
            'custom_layout',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            ['nullable' => true],
            'Author Custom Template'
        )->addColumn(
            'custom_layout_update_xml',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Author Custom Layout Update Content'
        )->addColumn(
            'custom_theme_from',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            200,
            ['nullable' => true],
            'Author Custom Theme Active From Date'
        )->addColumn(
            'custom_theme_to',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            200,
            ['nullable' => true],
            'Author Custom Theme Active To Date'
        )->addIndex(
            $installer->getIdxName('magefan_blog_author', ['identifier']),
            ['identifier']
        )->setComment(
            'Magefan Blog Author Table'
        );
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
