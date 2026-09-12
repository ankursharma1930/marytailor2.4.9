<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Plugin\Magefan\Blog\Model\ResourceModel\Post;

class Collection
{
    public function beforeAddFieldToFilter(
        \Magefan\Blog\Model\ResourceModel\Post\Collection $subject,
        $field, $condition
    ) {
        if ('authors' === $field) {
            $field = ['author_id', 'coauthor_id'];
            $condition = [$condition, $condition];

            if (!$subject->getFlag('coauthor_filter_added')) {
                $subject->setFlag('coauthor_filter_added', 1);

                $key = 'coauthor';
                $subject->getSelect()->joinLeft(
                    [$key . '_table' => $subject->getTable('magefan_blog_post_' . $key)],
                    'main_table.post_id = ' . $key . '_table.post_id',
                    []
                )->group(
                    'main_table.post_id'
                );
            }
        }
        return [$field, $condition];
    }
}