<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mageside\Recipe\Helper\Config;
use Mageside\Recipe\Model\FileUploader;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var string
     */
    protected $_mainTable = 'ms_recipe_filter';

    /**
     * @var FileUploader
     */
    protected $_fileUploader;

    /**
     * Store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $_helper;

    /**
     * Collection constructor.
     * @param StoreManagerInterface $storeManager
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'ms_recipe_filter',
        $resourceModel = \Mageside\Recipe\Model\ResourceModel\Recipe\Filter::class
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * Init select for recipe grid collection
     * @return Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->joinOptionData();

        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function joinOptionData()
    {
        $joinFields = [
            'type' => 'varchar',
        ];

        foreach ($joinFields as $fieldName => $backendType) {
            $tableName = $this->getTable('ms_recipe_filter_' . $backendType);

            $this->getSelect()
                ->joinLeft(
                    ["at_{$fieldName}" => $tableName],
                    "main_table.id = at_{$fieldName}.filter_id AND at_{$fieldName}.store_id= 0",
                    [$fieldName => "at_{$fieldName}.meta_value"]
                );
        }

        return $this->addFilterToMap('id', 'main_table.id');
    }

    /**
     * @inheridoc
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $linkedIds = $this->getColumnValues('id');
        if (count($linkedIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from(['filter_store' => $this->getTable('ms_recipe_filter_store')])
                ->where('filter_store.filter_id IN (?)', $linkedIds);
            $result = $connection->fetchAll($select);
            if ($result) {
                $storesData = [];
                foreach ($result as $storeData) {
                    $storesData[$storeData['filter_id']][] = $storeData['store_id'];
                }

                foreach ($this as $item) {
                    $linkedId = $item->getData('id');
                    if (!isset($storesData[$linkedId])) {
                        $storesData[$linkedId] = ['0'];
                    }
                    $storeIdKey = array_search(Store::DEFAULT_STORE_ID, $storesData[$linkedId], true);
                    if ($storeIdKey !== false) {
                        $stores = $this->storeManager->getStores(false, true);
                        $storeId = current($stores)->getId();
                        $storeCode = key($stores);
                    } else {
                        $storeId = current($storesData[$linkedId]);
                        if (isset($this->storeManager->getStores()[$storeId])) {
                            $storeCode = $this->storeManager->getStore($storeId)->getCode();
                        } else {
                            $storeCode = 0;
                        }
                    }
                    $item->setData('_first_store_id', $storeId);
                    $item->setData('store_code', $storeCode);
                    $item->setData('store_id', $storesData[$linkedId]);
                }
            }
        }
        return $this;
    }

    /**
     * @inheridoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'type') {
            $field = 'at_type.meta_value';
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
