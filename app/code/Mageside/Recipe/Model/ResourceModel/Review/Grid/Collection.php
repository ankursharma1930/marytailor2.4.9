<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\ResourceModel\Review\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    /**
     * @var bool
     */
    protected $_addStoreDataFlag = false;

    /**
     * Collection constructor.
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'review',
        $resourceModel = \Magento\Review\Model\ResourceModel\Review::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * Init select for review grid collection
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $reviewDetailTable = $this->getTable('review_detail');
        $reviewEntityTable = $this->getTable('review_entity');
        $reviewStatusTable = $this->getTable('review_status');
        $recipeVarcharTable = $this->getTable('ms_recipe_varchar');

        $inCond = $this->getConnection()
            ->prepareSqlCondition(
                'ret.entity_code',
                ['eq' => \Mageside\Recipe\Model\Review::RECIPE_CODE]
            );

        $this->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['review_id', 'created_at', 'entity_pk_value', 'recipe_id' => 'entity_pk_value', 'status_code' => 'status.status_code'])
            ->join(
                ['rdt' => $reviewDetailTable],
                'rdt.review_id = main_table.review_id',
                ['title', 'nickname', 'detail', 'customer_id', 'store_id']
            )
            ->join(
                ['recipe' => $recipeVarcharTable],
                'recipe.recipe_id = main_table.entity_pk_value
                 and recipe.meta_key=\'title\'
                 and recipe.store_id = 0',
                ['recipe_title' => 'recipe.meta_value', 'meta_value']
            )
            ->join(
                ['status' => $reviewStatusTable],
                'status.status_id = main_table.status_id',
                ['status_id' => 'status.status_id']
            )
            ->join(
                ['ret' => $reviewEntityTable],
                'ret.entity_id = main_table.entity_id AND ' . $inCond,
                null
            );

        $this->addFilterToMap('review_id', 'main_table.review_id');
        $this->addFilterToMap('created_at', 'main_table.created_at');
        $this->addFilterToMap('status_id', 'main_table.status_id');

        return $this;
    }
}
