<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    )
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('ms_recipe')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('ms_recipe')
            )
                ->addColumn(
                    'recipe_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Recipe ID'
                )
                ->addColumn(
                    'writer_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => true,
                    ],
                    'Writer ID'
                )
                ->addColumn(
                    'title',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Title'
                )
                ->addColumn(
                    'url_key',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'URL Key'
                )
                ->addColumn(
                    'type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    1,
                    [
                        'nullable' => false,
                    ],
                    'Type'
                )
                ->addColumn(
                    'thumbnail',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    [
                        'nullable' => true,
                    ],
                    'Thumbnail'
                )
                ->addColumn(
                    'path',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    1024,
                    [
                        'nullable' => false,
                    ],
                    'Path'
                )
                ->addColumn(
                    'prep_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Prepare Time'
                )
                ->addColumn(
                    'cook_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Cook Time'
                )
                ->addColumn(
                    'servings_number',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Servings Number'
                )
                ->addColumn(
                    'media_type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => true,
                    ],
                    'Media Type'
                )
                ->addColumn(
                    'media_type_image',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => true,
                    ],
                    'Media Type Image'
                )
                ->addColumn(
                    'media_type_video_url',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => true,
                    ],
                    'Media Type Video Url'
                )
                ->addColumn(
                    'short_description',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    [
                        'nullable' => true,
                    ],
                    'Short Description'
                )
                ->addColumn(
                    'ingredients',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    [
                        'nullable' => true,
                    ],
                    'Ingredients'
                )
                ->addColumn(
                    'method',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    [
                        'nullable' => true,
                    ],
                    'Method'
                )
                ->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Status'
                )
                ->setComment('Recipe Table');
            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('ms_recipe_product')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('ms_recipe_product')
            )
                ->addColumn(
                    'recipe_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Recipe ID'
                )
                ->addColumn(
                    'product_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Product ID'
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'ms_recipe_product',
                        'recipe_id',
                        'ms_recipe',
                        'recipe_id'
                    ),
                    'recipe_id',
                    $installer->getTable('ms_recipe'),
                    'recipe_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'ms_recipe_product',
                        'product_id',
                        'catalog_product_entity',
                        'entity_id'
                    ),
                    'product_id',
                    $installer->getTable('catalog_product_entity'),
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('Recipe Product Table');
            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('ms_recipe_store')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('ms_recipe_store')
            )
                ->addColumn(
                    'recipe_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Recipe ID'
                )
                ->addColumn(
                    'store_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Store ID'
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'ms_recipe_store',
                        'recipe_id',
                        'ms_recipe',
                        'recipe_id'
                    ),
                    'recipe_id',
                    $installer->getTable('ms_recipe'),
                    'recipe_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('Recipe Store Table');
            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('ms_recipe_filter')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('ms_recipe_filter'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Id'
                )
                ->addColumn(
                    'code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    32,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Filter Code'
                )
                ->addColumn(
                    'type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Type'
                )
                ->addIndex(
                    $installer->getIdxName(
                        'ms_recipe_filter',
                        ['code'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['code'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                );
            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('ms_recipe_filter_options')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('ms_recipe_filter_options'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Id'
                )
                ->addColumn(
                    'filter_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Filter Id'
                )
                ->addColumn(
                    'label',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Label'
                )
                ->addColumn(
                    'option_image',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Option Image'
                )
                ->addColumn(
                    'slug',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    1024,
                    [
                        'nullable' => false,
                    ],
                    'Slug'
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'ms_recipe_filter_options',
                        'filter_id',
                        'ms_recipe_filter',
                        'id'
                    ),
                    'filter_id',
                    $installer->getTable('ms_recipe_filter'),
                    'id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                );
            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('ms_recipe_filter_data')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('ms_recipe_filter_data'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Id'
                )
                ->addColumn(
                    'recipe_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Recipe Id'
                )
                ->addColumn(
                    'filter_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Filter Id'
                )
                ->addColumn(
                    'filter_options_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Filter Options Id'
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'ms_recipe_filter_data',
                        'recipe_id',
                        'ms_recipe',
                        'recipe_id'
                    ),
                    'recipe_id',
                    $installer->getTable('ms_recipe'),
                    'recipe_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'ms_recipe_filter_data',
                        'filter_id',
                        'ms_recipe_filter',
                        'id'
                    ),
                    'filter_id',
                    $installer->getTable('ms_recipe_filter'),
                    'id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'ms_recipe_filter_data',
                        'filter_options_id',
                        'ms_recipe_filter_options',
                        'id'
                    ),
                    'filter_options_id',
                    $installer->getTable('ms_recipe_filter_options'),
                    'id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                );
            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('ms_recipe_writer')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('ms_recipe_writer'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Id'
                )
                ->addColumn(
                    'customer_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Customer Id'
                )
                ->addColumn(
                    'is_writer',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                    ],
                    'Is Writer'
                )
                ->addColumn(
                    'about_writer',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'About Writer'
                )
                ->addColumn(
                    'avatar',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Avatar'
                )
                ->addColumn(
                    'website_link',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Website Link'
                )
                ->addColumn(
                    'nickname',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Writer Name'
                )
                ->addColumn(
                    'writer_url_key',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Writer Url Key'
                )
                ->addColumn(
                    'writer_image',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Writer Image'
                )
                ->addColumn(
                    'text_color',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Writer Info Text Color'
                )
                ->addColumn(
                    'youtube_link',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'YouTube Link'
                )
                ->addColumn(
                    'text_color',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Color Text'
                )
                ->addColumn(
                    'fb_link',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Facebook Link'
                )
                ->addColumn(
                    'in_link',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Instagram Link'
                )
                ->addColumn(
                    'snapchat_link',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Snapchat Link'
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'ms_recipe_writer',
                        'customer_id',
                        'customer_entity',
                        'entity_id'
                    ),
                    'customer_id',
                    $installer->getTable('customer_entity'),
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment(
                    'Writer Customer'
                );
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
