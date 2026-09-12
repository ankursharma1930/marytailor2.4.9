<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * InstallData constructor.
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(\Magento\Framework\App\ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $connection =  $this->resourceConnection->getConnection();

        $info = [
            'category_id' =>  $this->resourceConnection->getTableName('magefan_blog_category'),
            'post_id' =>  $this->resourceConnection->getTableName('magefan_blog_post')
        ];
        foreach ($info as $key => $value) {
            $tableName = $value . '_group';
            $connection->query("INSERT INTO `$tableName` (`$key`, `group_id`) SELECT `$key`, 0 FROM " . $value .
                ' ON DUPLICATE KEY UPDATE group_id=group_id');
        }
    }
}
