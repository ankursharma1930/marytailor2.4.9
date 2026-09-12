<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\ResourceModel;

/**
 * Class Option
 * @package Mageside\Recipe\Model\ResourceModel\Rating
 */
class Rating extends \Magento\Review\Model\ResourceModel\Rating
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Rating constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Review\Model\ResourceModel\Review\Summary $reviewSummary
     * @param \Magento\Framework\Registry $coreRegistry
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Review\Model\ResourceModel\Review\Summary $reviewSummary,
        \Magento\Framework\Registry $coreRegistry,
        $connectionName = null
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $logger, $moduleManager, $storeManager, $reviewSummary, $connectionName);
    }

    /**
     * @param \Magento\Review\Model\Rating $object
     * @return array
     */
    protected function _getEntitySummaryData($object)
    {
        $connection = $this->getConnection();

        $sumColumn = new \Zend_Db_Expr("SUM(rating_vote.{$connection->quoteIdentifier('percent')})");
        $countColumn = new \Zend_Db_Expr("COUNT(*)");

        $select = $connection->select()->from(
            ['rating_vote' => $this->getTable('rating_option_vote')],
            ['entity_pk_value' => 'rating_vote.entity_pk_value', 'sum' => $sumColumn, 'count' => $countColumn]
        )->join(
            ['review' => $this->getTable('review')],
            'rating_vote.review_id=review.review_id',
            []
        )->joinLeft(
            ['review_store' => $this->getTable('review_store')],
            'rating_vote.review_id=review_store.review_id',
            ['review_store.store_id']
        );
        if (!$this->_storeManager->isSingleStoreMode()) {
            $select->join(
                ['rating_store' => $this->getTable('rating_store')],
                'rating_store.rating_id = rating_vote.rating_id AND rating_store.store_id = review_store.store_id',
                []
            );
        }
        $select->join(
            ['review_status' => $this->getTable('review_status')],
            'review.status_id = review_status.status_id',
            []
        )->where(
            'review_status.status_code = :status_code'
        )->group(
            'rating_vote.entity_pk_value'
        )->group(
            'review_store.store_id'
        );
        $bind = [':status_code' => self::RATING_STATUS_APPROVED];

        if ($entityPkValue = $object->getEntityPkValue()) {
            $select->where('rating_vote.entity_pk_value = :pk_value');
            $bind[':pk_value'] = $entityPkValue;
        }

        if ($ratingEntityId = $this->_coreRegistry->registry('ratingEntityId')) {
            $inCond = $this->getConnection()
                ->prepareSqlCondition(
                    'review_rating.entity_id',
                    ['eq' => $ratingEntityId]
                );

            $select->join(
                ['review_rating' => $this->getTable('rating')],
                'rating_vote.rating_id = review_rating.rating_id AND ' . $inCond,
                []
            );
        }

        return $connection->fetchAll($select, $bind);
    }
}
