<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\BlogAuthor\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magefan\Blog\Model\ResourceModel\Comment;

class UpgradeData implements UpgradeDataInterface
{
    protected $commentResource;

    protected $_commentCollection;

    public function __construct(
        Comment $commentResource
    ) {
        $this->commentResource = $commentResource;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $version = $context->getVersion();

        if (version_compare($version, '2.10.10') < 0) {
            $connection = $this->commentResource->getConnection();

            $connection->delete(
                $this->commentResource->getTable('magefan_blog_author_store'),
                ['store_id = ?' => 0]
            );

            $authorSelect = $connection->select()->from(
                [$this->commentResource->getTable('magefan_blog_author')]
            );
            $authors = $connection->fetchAll($authorSelect);

            $count = count($authors);
            if ($count) {
                $data = [];
                foreach ($authors as $i => $author) {
                    $data[] = [
                        'author_id' => $author['author_id'],
                        'store_id' => 0,
                    ];

                    if (count($data) == 100 || $i == $count - 1) {
                        $connection->insertMultiple(
                            $this->commentResource->getTable('magefan_blog_author_store'),
                            $data
                        );
                        $data = [];
                    }
                }
            }
        }
    }
}
