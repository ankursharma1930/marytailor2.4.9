<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Model\ResourceModel;

use Magefan\Blog\Model\ResourceModel\Category;

/**
 * Class Category Plugin
 */
class CategoryPlugin
{
    /**
     * @param Category $resourceModel
     * @param $subject
     */
    public function afterSave(Category $resourceModel, $result, $subject)
    {
        $insert = isset($subject['data']['links']['post']) ? $subject['data']['links']['post'] : [];
        if (!count($insert)) {
            /*
             * Update only if have data,
             * does not allow to remove all posts from category edit page - it's OK
             */
            return $result;
        }

        $resource = $subject->getResource();
        $postCategoryTable = $resource->getTable('magefan_blog_post_category');
        $delete = $this->_getLoadByIdentifierSelect($subject, $postCategoryTable);

        if ($delete) {
            $where = ['category_id = ?' => (int)$subject->getCategoryId(), 'post_id IN (?)' => $delete];
            $resource->getConnection()->delete($postCategoryTable, $where);
        }

        $newItems = [];

        foreach ($insert as $value) {
            $newItems[] = [
                'post_id' => $value['id'],
                'category_id' => (int)$subject->getCategoryId(),
                'position' => isset($value['position']) ? $value['position'] : 0
            ];
        }

        $resource->getConnection()->insertMultiple($postCategoryTable, $newItems);

        return $result;
    }

    /**
     * @param $subject
     * @param $table
     * @return mixed
     */
    protected function _getLoadByIdentifierSelect($subject, $table)
    {
        $resource = $subject->getResource();
        $select = $resource->getConnection()->select()->from(
            ['cp' => $table]
        )->where(
            'cp.category_id = ?',
            $subject->getCategoryId()
        );

        $select->reset(\Zend_Db_Select::COLUMNS)->columns('cp.post_id');

        return $resource->getConnection()->fetchAll($select);
    }
}
