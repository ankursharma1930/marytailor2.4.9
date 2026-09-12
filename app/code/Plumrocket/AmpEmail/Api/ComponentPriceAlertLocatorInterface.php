<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface ComponentPriceAlertLocatorInterface extends \Plumrocket\AmpEmailApi\Model\LocatorInterface
{
    /**
     * @return ComponentPriceAlertLocatorInterface
     */
    public function resetData() : \Plumrocket\AmpEmailApi\Model\LocatorInterface;

    /**
     * @param int $productId
     * @return int|float
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getInitialPrice(int $productId);

    /**
     * @param int       $productId
     * @param int|float $price
     * @return mixed
     */
    public function setInitialPrice(int $productId, $price) : ComponentPriceAlertLocatorInterface;
}
