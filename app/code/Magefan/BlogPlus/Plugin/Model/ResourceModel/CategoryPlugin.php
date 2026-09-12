<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel;

use Magefan\Blog\Model\ResourceModel\Category;

/**
 * Class Category Plugin
 */
class CategoryPlugin
{
    /**
     * @var \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter
     */
    protected $customerGroupFilter;

    /**
     * CategoryPlugin constructor.
     * @param \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
     */
    public function __construct(
        \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
    ) {
        $this->customerGroupFilter = $customerGroupFilter;
    }

    /**
     * @param Category $resourceModel
     * @param $result
     * @param $subject
     * @return mixed
     */
    public function afterLoad(Category $resourceModel, $result, $subject)
    {
        $this->customerGroupFilter->loadGroupFilter($subject);

        return $result;
    }

    /**
     * @param Category $resourceModel
     * @param $result
     * @param $subject
     * @return mixed
     */
    public function afterSave(Category $resourceModel, $result, $subject)
    {
        $this->customerGroupFilter->saveGroupFilter($subject);

        return $result;
    }

    /**
     * @param Category $resourceModel
     * @param $result
     * @param $subject
     * @return mixed
     */
    public function afterDelete(Category $resourceModel, $result, $subject)
    {
        $this->customerGroupFilter->deleteGroupFilter($subject);

        return $result;
    }
}
