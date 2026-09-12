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

namespace Plumrocket\AmpEmailApi\Block;

interface ProductListComponentInterface
{
    /**
     * Retrieve src for amp list
     *
     * @return string
     */
    public function getListUrl() : string;

    /**
     * Check can show Add To Wishlist button
     *
     * @return bool
     */
    public function canShowWishlist() : bool;

    /**
     * Check can show Add To Cart button
     *
     * @return bool
     */
    public function canShowAddToCart() : bool;

    /**
     * Check can show product attribute, for example "name","price"
     *
     * @param string $attr
     * @return bool
     */
    public function canShowAttr(string $attr) : bool;
}
