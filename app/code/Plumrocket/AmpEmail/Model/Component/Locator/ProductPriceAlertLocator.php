<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\Locator;

use Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface;

class ProductPriceAlertLocator extends \Magento\Framework\DataObject implements ComponentPriceAlertLocatorInterface
{
    /**
     * @return \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface
     */
    public function resetData() : \Plumrocket\AmpEmailApi\Model\LocatorInterface
    {
        return $this->unsetData();
    }

    /**
     * @param int $productId
     * @return int|float
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getInitialPrice(int $productId)
    {
        if ($price = $this->_getData($productId)) {
            return $price;
        }

        throw new \Magento\Framework\Exception\NotFoundException(
            __('We cannot find initial price for product %1', $productId)
        );
    }

    /**
     * @param int       $productId
     * @param int|float $price
     * @return mixed
     */
    public function setInitialPrice(int $productId, $price) : ComponentPriceAlertLocatorInterface
    {
        return $this->setData($productId, $price);
    }
}
