<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RelatedProductsBlockLoadCollectionBefore implements ObserverInterface
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $collection = $observer->getData('collection');
        if (!$collection->isLoaded()) {
            $collection->getSelect()->columns([
                'display_on_post' => 'rl.display_on_post'
            ])->where('display_on_post = 0 OR display_on_post IS NULL');
        }
    }
}
