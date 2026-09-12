<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Model\Config\Source;

use Magefan\Blog\Model\Config\Source\PostsSortBy as Subject;

class PostsSortByPlugin
{
    const END_DATE_ASC = 100;
    const END_DATE_DESC = 101;

    /**
     * @param Subject $subject
     * @param array $result
     * @return array
     */
    public function afterToOptionArray(Subject $subject, array $result) : array
    {
        $firstPiece = array_splice($result, 0, 1);
        $resultArray = $firstPiece;

        $resultArray[] =  ['value' => self::END_DATE_ASC, 'label' => __('End Date - Ascending orde')];
        $resultArray[] =  ['value' => self::END_DATE_DESC, 'label' => __('End Date - Descending order')];

        return array_merge($resultArray, $result);
    }
}
