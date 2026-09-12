<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Cron;

/**
 * Class RelatedProductsRule
 */
class RelatedProductsRule
{

    /**
     *  Post's collection limit
     */
    const POST_COUNT = 100;

    /**
     * @var \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory
     */
    protected $postCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $datetime;

    /**
     * @var \Magefan\BlogPlus\Model\RelatedProductsRuleFactory
     */
    protected $relatedProductsRuleFactory;

    /**
     * RelatedProductRule constructor.
     * @param \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magefan\BlogPlus\Model\RelatedProductsRuleFactory $reletedProductsRuleFactory
     */
    public function __construct(
        \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magefan\BlogPlus\Model\RelatedProductsRuleFactory $relatedProductsRuleFactory
    ) {
        $this->postCollectionFactory = $postCollectionFactory;
        $this->datetime = $datetime;
        $this->relatedProductsRuleFactory = $relatedProductsRuleFactory;
    }
    public function execute()
    {
        // current date - 1 day
        $date = date(
            'Y-m-d H:i:s',
            $this->datetime->gmtTimestamp() - 86400
        );
        $posts = $this->postCollectionFactory->create()
            ->addActiveFilter()
            ->addFieldToFilter('rp_conditions_generation_time', ['lt' => $date ])
            ->setPageSize(self::POST_COUNT);

        foreach ($posts as $post) {
            $this->relatedProductsRuleFactory->create()->updateRelatedProducts($post);
        }
    }
}
