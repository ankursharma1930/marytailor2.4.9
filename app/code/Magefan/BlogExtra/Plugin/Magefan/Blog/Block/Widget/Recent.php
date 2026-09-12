<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Block\Widget;

use Magefan\Blog\Model\Config\Source\PostsSortBy;
use Magefan\Blog\Block\Widget\Recent as Subject;
use Magefan\BlogExtra\Plugin\Magefan\Blog\Model\Config\Source\PostsSortByPlugin;
use Magento\Framework\Api\SortOrder;

class Recent
{
    const SORTING_BY_POSITION = 'position';

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterGetPostCollection($subject, $result)
    {
        if ($subject->getCollectionOrderField() == self::SORTING_BY_POSITION && $subject->getData('category_id')) {
            $result->addFilterToMap('position', 'category_table.position');
        }

        return $result;
    }

    /**
     * @param Subject $subject
     * @param string $result
     * @return string
     */
    public function afterGetCollectionOrderField(Subject $subject,string $result): string
    {

        if ($result == self::SORTING_BY_POSITION && $subject->getData('category_id')) {
            $result = 'category_table.position';
        }

        $postsSortBy = (int)$subject->getData('posts_sort_by');
        if ($postsSortBy == PostsSortByPlugin::END_DATE_ASC || $postsSortBy == PostsSortByPlugin::END_DATE_DESC)
        {
            $result = 'end_time';
        }

        return $result;
    }

    /**
     * @param Subject $subject
     * @param string $result
     * @return string
     */
    public function afterGetCollectionOrderDirection(Subject $subject,string $result): string
    {
        $postsSortBy = (int)$subject->getData('posts_sort_by');

        if ($postsSortBy == PostsSortBy::POSITION || $postsSortBy == PostsSortByPlugin::END_DATE_ASC) {
            $result = SortOrder::SORT_ASC;
        }

        return $result;
    }
}
