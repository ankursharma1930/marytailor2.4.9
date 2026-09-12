<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Observer;

/**
 * Class FilterJoin
 */
class FilterJoin implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $joinOptions = $observer->getData('join_options');

        if ($joinOptions->getData('key') === 'category') {
            $joinOptions->setData('fields', 'position');
        }

        return $this;
    }
}
