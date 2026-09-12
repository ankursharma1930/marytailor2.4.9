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

namespace Plumrocket\Bestsellers\Model;

use Plumrocket\Bestsellers\Api\ProductIdsProviderInterface;

class PeriodList implements \Plumrocket\Bestsellers\Api\PeriodListInterface
{
    /**
     * @var array
     */
    private $periods;

    /**
     * PeriodList constructor.
     *
     * @param array $customPeriods
     */
    public function __construct(array $customPeriods = []) //@codingStandardsIgnoreLine
    {
        $this->periods = array_merge($this->getDefaultPeriods(), $customPeriods);
    }

    /**
     * @return array
     */
    private function getDefaultPeriods() : array
    {
        return [
            ProductIdsProviderInterface::PERIOD_DAY   => [
                'table' => 'day',
                'label' => __('Day'),
            ],
            ProductIdsProviderInterface::PERIOD_WEEK  => [
                'table' => 'day',
                'label' => __('Week'),
            ],
            ProductIdsProviderInterface::PERIOD_MONTH => [
                'table' => 'day',
                'label' => __('Month'),
            ],
            ProductIdsProviderInterface::PERIOD_YEAR  => [
                'table' => 'month',
                'label' => __('Year'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function getPeriods() : array
    {
        return $this->periods;
    }
}
