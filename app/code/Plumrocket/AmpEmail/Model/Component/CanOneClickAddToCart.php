<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component;

class CanOneClickAddToCart implements \Plumrocket\AmpEmail\Api\CanOneClickAddToCartInterface
{
    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function execute(\Magento\Catalog\Api\Data\ProductInterface $product) : bool
    {
        return $product->isSaleable()
            && $product->isInStock()
            && ! $product->getTypeInstance()->hasOptions($product)
            && \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE !== $product->getTypeId();
    }
}
