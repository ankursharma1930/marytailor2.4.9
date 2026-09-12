<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\ProductAlert;

class CurrentAlertsDataLocator
{
    /**
     * @var array|null
     */
    private $alertsData;

    /**
     * @param $customerId
     * @param $websiteId
     * @return array
     */
    public function getForCustomer(int $customerId, int $websiteId) : array
    {
        return $this->alertsData[$customerId][$websiteId] ?? [];
    }

    /**
     * @param array $alertsData
     * @return \Plumrocket\AmpEmail\Model\Component\ProductAlert\CurrentAlertsDataLocator
     */
    public function setAlertsData(array $alertsData) : self
    {
        $this->alertsData = $alertsData;
        return $this;
    }
}
