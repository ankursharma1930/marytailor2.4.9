<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Ui\DataProvider\Category\Form;

use Magefan\Blog\Ui\DataProvider\Category\Form\CategoryDataProvider;

/**
 * Class CategoryDataProviderPlugin
 */
class CategoryDataProviderPlugin
{
    /**
     * @var \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory
     */
    protected $postCollectionFactory;

    /**
     * CategoryDataProviderPlugin constructor.
     * @param \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory
     */
    public function __construct(
        \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory
    ) {
        $this->postCollectionFactory = $postCollectionFactory;
    }

    /**
     * @param CategoryDataProvider $subject
     * @param $result
     * @return mixed
     */
    public function afterGetData(CategoryDataProvider $subject, $result)
    {

        if (!$result) {
            return $result;
        }
        $keys = array_keys($result);
        if (!count($keys) && empty($keys[0])) {
            return $result;
        }
        $categoryId = $keys[0];

        $postCollection = $this->postCollectionFactory->create()
            ->addCategoryFilter($categoryId)
            ->setOrder('category_table.position', 'ASC')
            ->load();
        $items = [];
        foreach ($postCollection as $item) {
            $itemData = $item->getData();

            $itemData['id'] = $item->getId();
            /* Fix for big request data array */
            foreach ($itemData as $key => $value) {
                if (!in_array($key, ['id', 'post_id', 'title', 'position', 'record_id'])) {
                    unset($itemData[$key]);
                }
            }
            /* End */
            $items[] = $itemData;
        }

        $result[$categoryId]['data']['links']['post'] = $items;

        return $result;
    }
}
