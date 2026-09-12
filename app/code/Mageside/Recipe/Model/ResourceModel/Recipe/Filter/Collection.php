<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Recipe\Filter;

use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            \Mageside\Recipe\Model\Recipe\Filter::class,
            \Mageside\Recipe\Model\ResourceModel\Recipe\Filter::class
        );
    }

    /**
     * Collection constructor.
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param CollectionFactory $filterCollectionFactory
     * @param \Mageside\Recipe\Model\ResourceModel\RecipeProduct\CollectionFactory $recipeProductCollectionF
     * @param StoreManagerInterface $storeManager
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory $filterCollectionFactory,
        \Mageside\Recipe\Model\ResourceModel\RecipeProduct\CollectionFactory $recipeProductCollectionF,
        StoreManagerInterface $storeManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    public function addOptions()
    {
        foreach ($this->getItems() as $filter) {
            /** @var \Mageside\Recipe\Model\Recipe\Filter $filter */
            $filter->getOptions();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function joinOptionData()
    {
        $currentStoreId = $this->_storeManager->getStore()->getId();
        $joinFields = [
            'type' => 'varchar',
        ];

        foreach ($joinFields as $fieldName => $backendType) {
            $tableName = $this->getTable('ms_recipe_filter_' . $backendType);

            $this->getSelect()
                ->joinLeft(
                    ["at_{$fieldName}" => $tableName],
                    "main_table.id = at_{$fieldName}.filter_id
                    AND at_{$fieldName}.meta_key=\"{$fieldName}\"
                    AND at_{$fieldName}.store_id = 0",
                    null
                )
                ->joinLeft(
                    ["at_{$fieldName}_store" => $tableName],
                    "main_table.id = at_{$fieldName}_store.filter_id
                    AND at_{$fieldName}_store.meta_key=\"{$fieldName}\"
                    AND at_{$fieldName}_store.store_id = $currentStoreId",
                    null
                )
                ->columns([
                    $fieldName =>
                        new \Zend_Db_Expr(
                            "IFNULL(at_{$fieldName}_store.meta_value, at_{$fieldName}.meta_value)"
                        )
                ]);
        }

        return $this;
    }

    /**
     * @inheridoc
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $tableName = 'ms_recipe_filter_store';
        $linkField = 'id';

        $linkedIds = $this->getColumnValues($linkField);
        if (count($linkedIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from(['filter_store' => $this->getTable($tableName)])
                ->where('filter_store.filter_id IN (?)', $linkedIds);
            $result = $connection->fetchAll($select);
            if ($result) {
                $storesData = [];
                foreach ($result as $storeData) {
                    $storesData[$storeData['filter_id']][] = $storeData['store_id'];
                }

                foreach ($this as $item) {
                    $linkedId = $item->getData($linkField);
                    if (!isset($storesData[$linkedId])) {
                        $storesData[$linkedId] = ['0'];
                    }
                    $item->setData('store_id', $storesData[$linkedId]);
                }
            }
        }
        return $this;
    }
}
