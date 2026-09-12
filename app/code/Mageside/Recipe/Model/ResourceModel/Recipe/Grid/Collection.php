<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Recipe\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Psr\Log\LoggerInterface as Logger;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mageside\Recipe\Helper\Settings as SettingsHelper;

class Collection extends SearchResult
{
    /**
     * Store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string
     */
    protected $_idFieldName = 'recipe_id';

    /**
     * @var SettingsHelper
     */
    protected $settingsHelper;

    /**
     * Collection constructor.
     * @param StoreManagerInterface $storeManager
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param SettingsHelper $settingsHelper
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
        SettingsHelper $settingsHelper,
        $mainTable = 'ms_recipe',
        $resourceModel = \Mageside\Recipe\Model\ResourceModel\Recipe\Collection::class
    ) {
        $this->storeManager = $storeManager;
        $this->settingsHelper = $settingsHelper;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * Init select for recipe grid collection
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $connection = $this->getConnection();
        $this->getSelect()->columns(
            [
                'status' => new \Zend_Db_Expr('IF(status = 1, "Enable", "Disable")'),
            ]
        );

        $this->getSelect()
            ->joinLeft(
                ['writers' => $this->getTable('ms_recipe_writer')],
                'writers.customer_id = main_table.customer_id'
            )
            ->joinLeft(
                ['writer' => $this->getTable('customer_grid_flat')],
                'main_table.customer_id = writer.entity_id',
                ['writer_name' => $connection->getCheckSql(
                    'writers.is_writer = 0',
                    '"Customer is not a writer"',
                    $connection->getIfNullSql('name', '"Writer is not set"')
                )]
            );

        return $this;
    }

    /**
     * Perform operations after collection load
     * @return SearchResult
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return $this
     */
    protected function _afterLoad()
    {
        $this->performAfterLoad('ms_recipe_store', 'recipe_id');

        return parent::_afterLoad();
    }

    /**
     * Perform operations after collection load
     * @param string $tableName
     * @param string|null $linkField
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return void
     */
    protected function performAfterLoad($tableName, $linkField)
    {
        $linkedIds = $this->getColumnValues($linkField);
        if (count($linkedIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from(['recipe_entity_store' => $this->getTable($tableName)])
                ->where('recipe_entity_store.' . $linkField . ' IN (?)', $linkedIds);
            $result = $connection->fetchAll($select);
            if ($result) {
                $storesData = [];
                foreach ($result as $storeData) {
                    $storesData[$storeData[$linkField]][] = $storeData['store_id'];
                }

                foreach ($this as $item) {
                    $linkedId = $item->getData($linkField);
                    if (!isset($storesData[$linkedId])) {
                        continue;
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
    }

    /**
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function _beforeLoad()
    {
        $this->joinRecipeData();
        return parent::_beforeLoad();
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function joinRecipeData()
    {
        $currentStoreId = 0;
        $joinFields = $this->settingsHelper->getJoinFields();

        foreach ($joinFields as $fieldName => $backendType) {
            $tableName = $this->getTable('ms_recipe_' . $backendType);

            $this->getSelect()
                ->joinLeft(
                    ["at_{$fieldName}" => $tableName],
                    "main_table.recipe_id = at_{$fieldName}.recipe_id
                     AND at_{$fieldName}.meta_key=\"{$fieldName}\"
                      AND at_{$fieldName}.store_id = 0",
                    null
                )
                ->joinLeft(
                    ["at_{$fieldName}_store" => $tableName],
                    "main_table.recipe_id = at_{$fieldName}_store.recipe_id
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
        $this->addFilterToMap('nickname', 'main_table.writer');
        return $this;
    }
}
