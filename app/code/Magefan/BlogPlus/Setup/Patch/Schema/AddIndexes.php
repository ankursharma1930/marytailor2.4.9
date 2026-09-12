<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class AddIndexes implements SchemaPatchInterface
{

    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * Constructor
     *
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        $connection = $this->schemaSetup->getConnection();

        if ($connection->isTableExists($this->schemaSetup->getTable('magefan_blog_product_tmp'))) {
            $connection->addIndex(
                $this->schemaSetup->getTable('magefan_blog_product_tmp'),
                $this->schemaSetup->getIdxName(
                    $this->schemaSetup->getTable('magefan_blog_product_tmp'),
                    ['product_id']
                ),
                ['product_id']
            );

            $connection->addIndex(
                $this->schemaSetup->getTable('magefan_blog_product_tmp'),
                $this->schemaSetup->getIdxName(
                    $this->schemaSetup->getTable('magefan_blog_product_tmp'),
                    ['store_id']
                ),
                ['store_id']
            );
        }

        $this->schemaSetup->endSetup();
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
