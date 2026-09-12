<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\ResourceModel;

use Magento\Review\Model\ResourceModel\Rating\Option;

/**
 * Class Review
 * @package Mageside\Recipe\Model\ResourceModel
 */
class Review extends \Magento\Review\Model\ResourceModel\Review
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var Registry
     */
    protected $ratingEntityId = null;

    /**
     * Review constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Review\Model\RatingFactory $ratingFactory
     * @param Option $ratingOptions
     * @param \Magento\Framework\Registry $coreRegistry
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        Option $ratingOptions,
        \Magento\Framework\Registry $coreRegistry,
        $connectionName = null
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $date, $storeManager, $ratingFactory, $ratingOptions, $connectionName);
    }

    /**
     * @param int $entityPkValue
     * @param bool $approvedOnly
     * @param int $storeId
     * @return string
     */
    public function getTotalReviews($entityPkValue, $approvedOnly = false, $storeId = 0)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->_reviewTable,
            ['review_count' => new \Zend_Db_Expr('COUNT(*)')]
        )->where(
            "{$this->_reviewTable}.entity_pk_value = :pk_value"
        );

        $bind = [':pk_value' => $entityPkValue];

        if ($ratingEntityId = $this->_coreRegistry->registry('ratingEntityId')) {
            $select->where("{$this->_reviewTable}.entity_id = :entity_type");
            $bind[':entity_type'] = $ratingEntityId;
        }

        if ($storeId > 0) {
            $select->join(
                ['store' => $this->_reviewStoreTable],
                $this->_reviewTable . '.review_id=store.review_id AND store.store_id = :store_id',
                []
            );
            $bind[':store_id'] = (int)$storeId;
        }
        if ($approvedOnly) {
            $select->where("{$this->_reviewTable}.status_id = :status_id");
            $bind[':status_id'] = \Magento\Review\Model\Review::STATUS_APPROVED;
        }

        return $connection->fetchOne($select, $bind);
    }
}
