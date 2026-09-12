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
 * @package     Plumrocket_Bestsellers
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\Bestsellers\Model\Report\Fallback;

class EmptyStrategy implements \Plumrocket\Bestsellers\Model\FallbackStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function generateIdList(array $productIds, int $limit, int $storeId, int $categoryId = 0) : array
    {
        return [];
    }
}
