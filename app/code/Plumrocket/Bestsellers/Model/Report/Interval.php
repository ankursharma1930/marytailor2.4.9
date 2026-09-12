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

namespace Plumrocket\Bestsellers\Model\Report;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Plumrocket\Bestsellers\Api\ProductIdsProviderInterface;

class Interval
{
    /**
     * @var array
     */
    private $intervals = [];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var object[]
     */
    private $customIntervals;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param array                                       $customIntervals
     */
    public function __construct(
        DateTime $dateTime,
        array $customIntervals = []
    ) {
        $this->dateTime = $dateTime;
        $this->customIntervals = $customIntervals;
    }

    /**
     * @param string $period
     * @return \DateTime[]|false
     */
    public function getByPeriod(string $period)
    {
        if (! isset($this->intervals[$period])) {
            $interval = $this->tryUseCustomIntervals($period);

            if (! $interval) {
                switch ($period) {
                    case ProductIdsProviderInterface::PERIOD_DAY:
                        $interval = [
                            'start' => $this->dateTime->gmtDate('Y-m-d', 'today'),
                            'end' => $this->dateTime->gmtDate('Y-m-d', 'today'),
                        ];
                        break;
                    case ProductIdsProviderInterface::PERIOD_WEEK:
                        $interval = [
                            'start' => $this->dateTime->gmtDate('Y-m-d', '-7 days'),
                            'end' => $this->dateTime->gmtDate('Y-m-d', 'now'),
                        ];
                        break;
                    case ProductIdsProviderInterface::PERIOD_MONTH:
                        $interval = [
                            'start' => $this->dateTime->gmtDate('Y-m-d', '-1 Month'),
                            'end' => $this->dateTime->gmtDate('Y-m-d', 'now'),
                        ];
                        break;
                    case ProductIdsProviderInterface::PERIOD_YEAR:
                        $interval = [
                            'start' => $this->dateTime->gmtDate('Y-m-d', '-12 Month'),
                            'end' => $this->dateTime->gmtDate('Y-m-d', 'now'),
                        ];
                        break;
                    default:
                        $interval = false;
                }
            }

            $this->intervals[$period] = $interval;
        }

        return $this->intervals[$period];
    }

    /**
     * @param string $period
     * @return bool|mixed
     */
    private function tryUseCustomIntervals(string $period)
    {
        if (! empty($this->customIntervals) && isset($this->customIntervals[$period])) {
            try {
                $interval = $this->customIntervals[$period]->getInterval();
            } catch (\Exception $exception) {
                $interval = false;
            }
        } else {
            $interval = false;
        }

        return $interval;
    }
}
