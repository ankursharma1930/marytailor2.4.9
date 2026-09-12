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

namespace Plumrocket\Bestsellers\Api;

interface ProductIdsProviderInterface
{
    /**
     * Built-In fallback types
     */
    public const FALLBACK_RANDOM = 'random';
    public const FALLBACK_EMPTY = 'empty';

    /**
     * Periods for select
     */
    public const PERIOD_DAY = 'day';
    public const PERIOD_WEEK = 'week';
    public const PERIOD_MONTH = 'month';
    public const PERIOD_YEAR = 'year';

    /**
     * @param string $period
     * @param int    $count
     * @param string $fallback
     * @return array
     */
    public function getByPeriod(string $period, int $count = 5, string $fallback = self::FALLBACK_EMPTY): array;

    /**
     * @param int    $count
     * @param string $fallback
     * @return array
     */
    public function getDaily(int $count = 5, string $fallback = self::FALLBACK_EMPTY): array;

    /**
     * @param int    $count
     * @param string $fallback
     * @return array
     */
    public function getWeekly(int $count = 5, string $fallback = self::FALLBACK_EMPTY): array;

    /**
     * @param int    $count
     * @param string $fallback
     * @return array
     */
    public function getMonthly(int $count = 5, string $fallback = self::FALLBACK_EMPTY): array;

    /**
     * @param int    $count
     * @param string $fallback
     * @return array
     */
    public function getYearly(int $count = 5, string $fallback = self::FALLBACK_EMPTY): array;

    /**
     * @param string $period
     * @param int    $categoryId
     * @param int    $count
     * @param string $fallback
     * @return array
     */
    public function getByCategory(
        string $period,
        int $categoryId,
        int $count = 5,
        string $fallback = self::FALLBACK_EMPTY
    ): array;

    /**
     * @param string $period
     * @param int    $count
     * @param array  $exclude
     * @param array  $idPool
     * @return array
     */
    public function get(
        string $period,
        int $count = 5,
        array $exclude = [],
        array $idPool = []
    ): array;
}
