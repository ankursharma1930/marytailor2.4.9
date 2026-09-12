<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component;

class WishlistProductsResolver
{
    /**
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * WishlistProductsResolver constructor.
     *
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     */
    public function __construct(\Magento\Wishlist\Model\WishlistFactory $wishlistFactory)
    {
        $this->wishlistFactory = $wishlistFactory;
    }

    /**
     * @param int  $customerId
     * @param bool $force
     * @return array
     */
    public function execute(int $customerId, bool $force = false) : array
    {
        if (0 === $customerId) {
            return [];
        }

        if (! array_key_exists($customerId, $this->cache) || $force) {
            /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
            $wishlist = $this->wishlistFactory->create();
            $wishlist->loadByCustomerId($customerId, true);

            $collection = $wishlist->getItemCollection();
            $collection->addFieldToSelect('product_id');

            $this->cache[$customerId] = $collection->getColumnValues('product_id');
        }

        return $this->cache[$customerId];
    }
}
