<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Ui\DataProvider\Listing\Collection;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Psr\Log\LoggerInterface as Logger;
use Mageside\Recipe\Model\ResourceModel\Recipe;

class Review extends SearchResult
{
    /**
     * Review constructor.
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
        $mainTable = 'ms_recipe',
        $resourceModel = Recipe::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    protected function _initSelect()
    {
        $this->getSelect()
            ->from(['main_table' => $this->getMainTable()])
            ->joinLeft(
                ["store_attrtable"=>$this->getTable("ms_recipe_varchar")],
                "store_attrtable.recipe_id = main_table.recipe_id 
                         and store_attrtable.store_id = 0 and store_attrtable.meta_key = 'title'",
                null
            )
            ->columns(['recipe_id', 'thumbnail', 'title' =>'store_attrtable.meta_value']);

        return $this;
    }
}
