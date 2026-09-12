<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model;

/**
 * Interface LoginCustomerByIdInterface
 *
 * @api
 * @since 1.0.1
 */
interface LoginCustomerByIdInterface
{
    /**
     * @param int $customerId
     * @param int $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(int $customerId, int $storeId) : bool;
}
