<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogAuthor\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class PostCollectionLoadAfter
 */
class PostCollectionLoadAfter implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $collection = $observer->getData('blog_post_collection');

        $items = $collection->getColumnValues('post_id');

        if (count($items)) {
            $key = 'coauthor';
            $tableName = $collection->getTable('magefan_blog_post_' . $key);
            $connection = $collection->getConnection();
            $select = $connection->select()
                ->from(['bpc' => $tableName])
                ->where('bpc.post_id IN (?)', $items);

            $result = $connection->fetchAll($select);
            if ($result) {
                $data = [];
                foreach ($result as $item) {
                    $data[$item['post_id']][] = $item[$key . '_id'];
                }

                foreach ($collection as $item) {
                    $postId = $item->getData('post_id');
                    if (isset($data[$postId])) {
                        $item->setData('coauthors', $data[$postId]);
                    }
                }
            }
        }
    }
}
