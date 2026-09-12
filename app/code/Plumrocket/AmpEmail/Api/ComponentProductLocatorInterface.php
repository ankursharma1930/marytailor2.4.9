<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface ComponentProductLocatorInterface extends \Plumrocket\AmpEmailApi\Model\LocatorInterface
{
    /**
     * @return ComponentProductLocatorInterface
     */
    public function resetData() : \Plumrocket\AmpEmailApi\Model\LocatorInterface;

    /**
     * @param array $productIds
     * @return \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface
     */
    public function setProductIds(array $productIds) : ComponentProductLocatorInterface;

    /**
     * @return int[]
     */
    public function getProductIds() : array;

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     * @return \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface
     */
    public function setProducts(array $products) : ComponentProductLocatorInterface;

    /**
     * In manual testing method can return products from order
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getProducts() : array;
}
