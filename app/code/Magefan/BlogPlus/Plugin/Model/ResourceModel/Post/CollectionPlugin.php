<?php

/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel\Post;

use Magefan\Blog\Model\ResourceModel\Post\Collection;

/**
 * Class CollectionPlugin
 */
class CollectionPlugin
{
    /**
     * @var \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter
     */
    protected $customerGroupFilter;

    /**
     * CollectionPlugin constructor.
     * @param \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
     */
    public function __construct(
        \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
    ) {
        $this->customerGroupFilter = $customerGroupFilter;
    }

    /**
     * @param Collection $subject
     * @param $result
     * @return mixed
     */
    public function afterAddActiveFilter(Collection $subject, $result)
    {
        $this->customerGroupFilter->addGroupFilter($subject);
        return $result;
    }
}
