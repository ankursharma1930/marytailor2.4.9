<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_AmpEmailApi
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmailApi\Api;

interface InitFrontProductPriceInterface
{
    /**
     * Set final price with catalog price rules and taxes
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface                 $componentDataLocator
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function execute(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator
    ) : \Magento\Catalog\Api\Data\ProductInterface;
}
