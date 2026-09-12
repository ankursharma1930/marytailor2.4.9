<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Block\Category;

/**
 * Class PostListPlugin
 * @package Magefan\BlogExtra\Plugin\Magefan\Blog\Block\Category
 */
use Magefan\Blog\Model\Config\Source\PostsSortBy;
use Magento\Framework\Api\SortOrder;
use Magefan\BlogExtra\Plugin\Magefan\Blog\Model\Config\Source\PostsSortByPlugin;
use Magento\Store\Model\ScopeInterface;

class PostListPlugin
{
    const SORTING_BY_POSITION = 'position';

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterGetPostCollection($subject, $result)
    {
        if ($subject->getCollectionOrderField() == self::SORTING_BY_POSITION) {
            $result->addFilterToMap('position', 'category_table.position');
        }

        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return string
     */
    public function afterGetCollectionOrderField($subject, $result)
    {
        if ($result == self::SORTING_BY_POSITION) {
            $result = 'category_table.position';
        }

        $postsSortBy = $subject->getCategory()->getData('posts_sort_by');
        if ($postsSortBy == PostsSortByPlugin::END_DATE_ASC || $postsSortBy == PostsSortByPlugin::END_DATE_DESC) {
            $result = 'end_time';
        }

        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return string
     */
    public function afterGetCollectionOrderDirection($subject, $result)
    {
        $postsSortBy = $subject->getCategory()->getData('posts_sort_by');
        if ($postsSortBy == PostsSortBy::POSITION || $postsSortBy == PostsSortByPlugin::END_DATE_ASC) {
            $result = SortOrder::SORT_ASC;
        }

        return $result;
    }
}
