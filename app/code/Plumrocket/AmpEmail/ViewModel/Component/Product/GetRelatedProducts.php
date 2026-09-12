<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\ViewModel\Component\Product;

class GetRelatedProducts
{
    /**
     * @var \Magento\Catalog\Model\Product\LinkFactory
     */
    private $productLinkFactory;

    /**
     * GetRelatedProducts constructor.
     *
     * @param \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory
     */
    public function __construct(\Magento\Catalog\Model\Product\LinkFactory $productLinkFactory)
    {
        $this->productLinkFactory = $productLinkFactory;
    }

    /**
     * Get related product ids
     *
     * @param array $productIds
     * @param int $storeId
     * @param int $count
     * @return array
     */
    public function execute(array $productIds, int $storeId, int $count) : array
    {
        if (empty($productIds)) {
            return [];
        }

        $items = [];

        $collection = $this->productLinkFactory->create()->useRelatedLinks()
            ->getProductCollection()
            ->setStoreId($storeId)
            ->addAttributeToSelect('*')
            ->addProductFilter($productIds);

        $collection->getSelect()->group('e.entity_id');

        $collection->addPriceData()
            ->setPositionOrder()
            ->load();

        if ($collection->getSize()) {
            foreach ($collection as $item) {
                if (! isset($items[$item->getId()]) && ! in_array($item->getId(), $productIds)) {
                    $items[$item->getId()] = $item;
                }
            }
        }

        if (! empty($items)) {
            $items = $this->randomize($items, $count);
        }

        return $items;
    }

    /**
     * Randomize array and cut elements
     *
     * @param array $items
     * @param int   $count
     * @return array
     */
    private function randomize($items, $count) : array
    {
        shuffle($items);
        if (count($items) > $count) {
            array_splice($items, $count);
        }

        return $items;
    }
}
