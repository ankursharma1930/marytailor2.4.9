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

class BestsellersReport
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $reportType;

    /**
     * BestsellersReport constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param                                           $reportType
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $reportType)
    {
        $this->objectManager = $objectManager;
        $this->reportType = $reportType;
    }

    /**
     * Refresh bestsellers statistics
     *
     * @return void
     * @throws \Exception
     */
    public function refresh()
    {
        $this->objectManager->create($this->reportType)->aggregate(); //@codingStandardsIgnoreLine
    }
}
