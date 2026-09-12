<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

/**
 * Interface IsCanOneClickAddToCartInterface
 *
 * @package Plumrocket\AmpEmail\Api
 */
interface CanOneClickAddToCartInterface
{
    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function execute(\Magento\Catalog\Api\Data\ProductInterface $product) : bool;
}
