<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Observer;

class ResetLocatorsObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Plumrocket\AmpEmail\Api\ComponentLocatorPoolInterface
     */
    private $componentLocatorPool;

    /**
     * ResetLocatorsObserver constructor.
     *
     * @param \Plumrocket\AmpEmail\Api\ComponentLocatorPoolInterface $componentLocatorPool
     */
    public function __construct(\Plumrocket\AmpEmail\Api\ComponentLocatorPoolInterface $componentLocatorPool)
    {
        $this->componentLocatorPool = $componentLocatorPool;
    }

    /**
     * Don't need check if module enabled
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        foreach ($this->componentLocatorPool->getList() as $locator) {
            $locator->resetData();
        }
    }
}
