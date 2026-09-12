<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Model;

/**
 * Class RelatedProductsRule
 */
class RelatedProductsRule
{

    const POSITION = 100;

    /**
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $datetime;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Iterator
     */
    protected $iterator;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array|null
     */
    protected $productIds;

    /**
     * RelatedProductsRule constructor.
     * @param \Magento\CatalogRule\Model\RuleFactory $ruleFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Model\ResourceModel\Iterator $iterator
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\CatalogRule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Model\ResourceModel\Iterator $iterator,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->datetime = $datetime;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productFactory = $productFactory;
        $this->iterator = $iterator;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magefan\Blog\Model\Post $post
     */
    public function updateRelatedProducts(\Magefan\Blog\Model\Post $post)
    {
        $postId = $post->getId();
        if (!$postId) {
            return;
        }

        /* Get related product ids by condition rule */
        $productIds = [];
        if ($cs = $post->getData('rp_conditions_serialized')) {
            $rule = $this->ruleFactory->create();
            $array = @json_decode($cs, true);
            if ($array) {
                $hasConditions = isset($array['conditions']);
            } else {
                $hasConditions = (strpos($cs, '"conditions"') !== false); //fix for M2.1.x
            }

            if ($hasConditions) {
                $rule->setData('conditions_serialized', $cs);
                $rule->loadPost($rule->getData());

                $productIds = $this->getListProductIds($rule);
                $productIds = array_unique($productIds);
            }
        }

        $resource = $post->getResource();
        $connection = $resource->getConnection();

        /* Remove old related products */
        $table = $resource->getTable('magefan_blog_post_relatedproduct');
        $connection->delete(
            $table,
            [
                'post_id = ?' => $postId,
                'related_by_rule = ?' => 1
            ]
        );

        if (count($productIds)) {
            $select = $resource->getConnection()->select()
                ->from(['rp' => $table])
                ->where('rp.post_id = ?', $postId)
                ->where('rp.related_id IN (?)', $productIds);

            $removeRelatedIds = [];
            foreach ($connection->fetchAll($select) as $item) {
                if ($item['auto_related']) {
                    $removeRelatedIds[] = $item['related_id'];
                } else {
                    /* Remove from related by rule */
                    unset($productIds[array_search($item['related_id'], $productIds)]);
                }
            }

            /* Remove autorelated products. Related rule has higher priority */
            if (count($removeRelatedIds)) {
                $connection->delete(
                    $table,
                    [
                        'related_id IN (?)' => $removeRelatedIds,
                    ]
                );
            }

            /* Add new related products */
            if (count($productIds)) {
                $data = [];
                $position = self::POSITION;
                foreach ($productIds as $productId) {
                    $data[] = [
                        'post_id' => $postId,
                        'related_id' => $productId,
                        'position' => $position,
                        'auto_related' => 0,
                        'display_on_product' => 0, // 0 == yes
                        'display_on_post' => 0, // 0 == yes
                        'related_by_rule' => 1,
                    ];
                    $position++;

                    if ($position > 100 + self::POSITION) {
                        break; // limit = 100 products
                    }
                }

                $connection->insertMultiple($table, $data);
            }
        }

        /* Update last generation time */
        $generationDate = date(
            'Y-m-d H:i:s',
            $this->datetime->gmtTimestamp()
        );

            $connection->update(
                $resource->getTable('magefan_blog_post'),
                ['rp_conditions_generation_time' => $generationDate],
                ['post_id = ?' => $postId]
            );
    }

    protected function getListProductIds($rule)
    {
        $productCollection = $this->productCollectionFactory->create();
        $this->productIds = [];
        $rule->setCollectedAttributes([]);
        $rule->getConditions()->collectValidatedAttributes($productCollection);
        $this->iterator->walk(
            $productCollection->getSelect(),
            [[$this, 'callbackValidateProduct']],
            [
                'attributes' => $rule->getCollectedAttributes(),
                'product' => $this->productFactory->create(),
                'rule' => $rule
            ]
        );
        return $this->productIds;
    }

    /**
     * Callback function for product matching
     *
     * @param array $args
     * @return void
     */
    public function callbackValidateProduct($args)
    {
        $product = clone $args['product'];
        $product->setData($args['row']);

        $rule = $args['rule'];

        $websites = $this->getWebsitesMap();
        foreach ($websites as $websiteId => $defaultStoreId) {
            $product->setStoreId($defaultStoreId);
            if ($rule->getConditions()->validate($product)) {
                $this->productIds[] = $product->getId();
            }
        }
    }

    /**
     * Prepare website map
     *
     * @return array
     */
    protected function getWebsitesMap()
    {
        $map = [];
        $websites = $this->storeManager->getWebsites();
        foreach ($websites as $website) {
            if ($website->getDefaultStore() === null) {
                continue;
            }
            $map[$website->getId()] = $website->getDefaultStore()->getId();
        }
        return $map;
    }
}
