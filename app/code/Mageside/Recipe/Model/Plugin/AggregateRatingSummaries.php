<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\Plugin;

/**
 * Class AggregateRatingSummaries
 * @package Mageside\Recipe\Model\Plugin
 */
class AggregateRatingSummaries
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * AggregateRatingSummaries constructor.
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
    }

    public function beforeAggregate(\Magento\Review\Model\ResourceModel\Review $subject, $review)
    {
        $ratingEntityId = $this->_coreRegistry->registry('ratingEntityId');
        if ($ratingEntityId) {
            $this->_coreRegistry->unregister('ratingEntityId');
            $this->_coreRegistry->register('ratingEntityId', $review->getEntityId());
        } else {
            $this->_coreRegistry->register('ratingEntityId', $review->getEntityId());
        }
    }
}
