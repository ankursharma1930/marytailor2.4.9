<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Plugin\Model;

use Magefan\Blog\Model\Post;

/**
 * Class Post Plugin
 */
class PostPlugin
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter
     */
    protected $customerGroupFilter;

    /**
     * PostPlugin constructor.
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
    ) {
        $this->customerGroupFilter = $customerGroupFilter;
        $this->request = $request;
    }

    /**
     * @param Post $subject
     * @param $result
     * @return mixed
     */
    public function afterGetRelatedProducts(Post $subject, $result)
    {
        $result->getSelect()->columns([
            'display_on_product' => 'rl.display_on_product',
            'display_on_post' => 'rl.display_on_post',
            'auto_related' => 'rl.auto_related',
            'related_by_rule' => 'rl.related_by_rule'
        ]);

        return $result;
    }

    /**
     * @param Post $subject
     * @param $result
     * @return mixed
     */
    public function afterGetRelatedPosts(Post $subject, $result)
    {
        $result->getSelect()->columns([
            'auto_related' => 'rl.auto_related'
        ]);

        return $result;
    }

    /**
     * @param Post $subject
     * @param $result
     * @param $subject
     * @return bool
     */
    public function afterIsVisibleOnStore(Post $subject, $result)
    {
        if (!$result) {
            return $result;
        }

        return $this->customerGroupFilter->isVisibleForGroup($subject);
    }
}
