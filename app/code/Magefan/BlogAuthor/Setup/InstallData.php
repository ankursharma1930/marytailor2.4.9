<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\State;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var State
     */
    private $state;

    /**
     * InstallData constructor.
     * @param ResourceConnection $resourceConnection
     * @param State $state
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        State $state
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->state = $state;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        try {
            $this->state->setAreaCode('adminhtml');
        } catch (\Exception $e) {
        }
        $connection =  $this->resourceConnection->getConnection();

            $author = $this->resourceConnection->getTableName('magefan_blog_author');
            $user =  $this->resourceConnection->getTableName('admin_user');

            $sql = 'INSERT INTO `'. $author .'` (`author_id`, `is_active`, `firstname`, `lastname`, `email`, `identifier`) 
                SELECT `user_id`, 1, `firstname`, `lastname`, `email`, LOWER(concat(firstname,"-",lastname)) FROM ' . $user;
            $connection->query($sql);
    }
}
