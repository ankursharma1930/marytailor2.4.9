<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\ResourceModel\Recipe;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'recipe_id';

    /**
     * @var Filter\CollectionFactory
     */
    protected $_filterCollectionFactory;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\RecipeProduct\CollectionFactory
     */
    protected $_recipeProductCollectionF;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var \Mageside\Recipe\Helper\Config
     */
    protected $helper;

    /**
     * @var $storeId
     */
    protected $storeId;

    /**
     * @var \Mageside\Recipe\Helper\Settings
     */
    protected $settingsHelper;

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(
            \Mageside\Recipe\Model\Recipe::class,
            \Mageside\Recipe\Model\ResourceModel\Recipe::class
        );
    }

    /**
     * Collection constructor.
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param Filter\CollectionFactory $filterCollectionFactory
     * @param \Mageside\Recipe\Model\ResourceModel\RecipeProduct\CollectionFactory $recipeProductCollectionF
     * @param \Mageside\Recipe\Helper\Config $helper
     * @param \Mageside\Recipe\Helper\Settings $settingsHelper
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
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
        \Mageside\Recipe\Helper\Config $helper,
        \Mageside\Recipe\Helper\Settings $settingsHelper,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->_filterCollectionFactory = $filterCollectionFactory;
        $this->_recipeProductCollectionF = $recipeProductCollectionF;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->_request = $request;
        $this->settingsHelper = $settingsHelper;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * @param $filters
     * @return $this
     */
    public function applySelectedFilters($filters)
    {
        $filterCollection = $this->_filterCollectionFactory->create();
        $select = $this->getSelect();
        foreach ($filterCollection->getItems() as $filter) {
            if (isset($filters[$filter->getCode()])) {
                $aliasDataTable = 'filter_' . $filter->getCode();
                $aliasOptionsTable = 'filter_option_' . $filter->getCode();
                $select
                    ->join(
                        [$aliasDataTable => $this->getTable('ms_recipe_filter_data')],
                        'main_table.recipe_id = ' . $aliasDataTable . '.recipe_id AND '
                        . $aliasDataTable . '.filter_id = \'' . $filter->getId() . '\'',
                        []
                    )
                    ->join(
                        [$aliasOptionsTable => $this->getTable('ms_recipe_filter_options')],
                        $aliasDataTable . '.filter_options_id = ' . $aliasOptionsTable . '.id AND '
                        . $aliasOptionsTable . '.slug = \'' . $filters[$filter->getCode()] . '\'',
                        []
                    );
            }
        }

        return $this;
    }

    /**
     * @param $productId
     * @return $this
     */
    public function applyFilterByProductId($productId)
    {
        $inCond = $this->getConnection()
            ->prepareSqlCondition(
                'product.product_id',
                ['eq' => $productId]
            );

        $this->getSelect()->join(
            ['product' => $this->getTable('ms_recipe_product')],
            'main_table.recipe_id = product.recipe_id AND ' . $inCond,
            []
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad()
    {
        $filterOptions = $this->loadAdditionalOptions();
        $recipeStores = $this->loadStoresData();

        foreach ($this->getItems() as $recipe) {
            if (!empty($filterOptions)) {
                if (array_key_exists($recipe->getId(), $filterOptions)) {
                    $recipe->addData(['options' => $filterOptions[$recipe->getId()]]);
                }
            }
            if (!empty($recipeStores)) {
                if (array_key_exists($recipe->getId(), $recipeStores)) {
                    $recipe->addData(['store_view' => $recipeStores[$recipe->getId()]]);
                }
            }
            if ($servingNumber = $recipe->getServingsNumber()) {
                $numbers = explode('-', $servingNumber);
                $recipe->setServingsNumberFrom(trim($numbers[0]));
                $recipe->setServingsNumberTo(trim($numbers[1]));
            }
            if ($ingredients = $recipe->getIngredients()) {
                $recipe->setIngredients(json_decode($ingredients, true));
            }
            if ($method = $recipe->getMethod()) {
                $recipe->setMethod(json_decode($method, true));
            }
        }

        parent::_afterLoad();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function loadAdditionalOptions()
    {
        $options = [];
        $ids = $this->getAllIds();
        if (!empty($ids)) {
            $connection = $this->getConnection();
            $currentStoreId = $this->storeManager->getStore()->getId();
            $joinFields = [
                'label' => 'options_varchar',
                'option_image' => 'options_varchar',
            ];

            $select = $connection->select()
                ->from(['main_table' => $this->getTable('ms_recipe_filter_data')])
                ->joinLeft(
                    ['filter' => $this->getTable('ms_recipe_filter')],
                    'main_table.filter_id = filter.id',
                    ['code']
                )
                ->where('main_table.recipe_id in (?)', $ids);

            foreach ($joinFields as $fieldName => $backendType) {
                $tableName = $this->getTable('ms_recipe_filter_' . $backendType);

                $select = $select
                    ->joinLeft(
                        ["at_{$fieldName}" => $tableName],
                        "main_table.filter_options_id = at_{$fieldName}.filter_option_id 
                        AND at_{$fieldName}.meta_key=\"{$fieldName}\" 
                        AND at_{$fieldName}.store_id = 0",
                        null
                    )
                    ->joinLeft(
                        ["at_{$fieldName}_store" => $tableName],
                        "main_table.filter_options_id = at_{$fieldName}_store.filter_option_id 
                        AND at_{$fieldName}_store.meta_key=\"{$fieldName}\" 
                        AND at_{$fieldName}_store.store_id = $currentStoreId",
                        null
                    )
                    ->columns([
                        "option_$fieldName" => new \Zend_Db_Expr(
                            "IFNULL(at_{$fieldName}_store.meta_value, at_{$fieldName}.meta_value)"
                        )
                    ]);
            }

            $filtersData = $connection->fetchAll($select);

            if (!empty($filtersData)) {
                foreach ($filtersData as $record) {
                    $options[$record['recipe_id']][$record['code']][$record['filter_options_id']] = [
                        'option_id' => $record['filter_options_id'],
                        'option_label' => $record['option_label'],
                        'option_image' => $record['option_option_image']
                    ];
                }
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    private function loadStoresData()
    {
        $stores = [];
        $ids = $this->getAllIds();
        if (!empty($ids)) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from(['main_table' => $this->getTable('ms_recipe_store')])
                ->where('main_table.recipe_id in (?)', $ids);

            $storesData = $connection->fetchAll($select);

            if (!empty($storesData)) {
                foreach ($storesData as $record) {
                    $stores[$record['recipe_id']][] = $record['store_id'];
                }
            }
        }

        return $stores;
    }

    /**
     * @param $keyword
     * @return $this
     */
    public function applySearchKeywordFilter($keyword)
    {
        $attributesPriorities = [];
        foreach ($this->settingsHelper->getSearchKeywords() as $field => $priority) {
            $expression = "IFNULL(at_{$field}_store.meta_value, at_{$field}.meta_value)";
            $attributesPriorities[$expression] = $priority;
        }

        $keywordWords = explode(' ', $keyword);

        $weightColumnExpression = [];
        $connection = $this->getConnection();
        foreach ($attributesPriorities as $attribute => $priority) {
            $conditions = [];
            foreach ($keywordWords as $keywordWord) {
                if ($keywordWord = trim($keywordWord)) {
                    $conditions[] = $connection->quoteInto(
                        "(LOWER({$attribute}) LIKE LOWER(?))",
                        ['like' => '%' . $keywordWord . '%']
                    );
                }
            }
            $condition = implode(' AND ', $conditions);
            $weightColumnExpression[] = $connection->getCheckSql($condition, $priority, 0);
        }
        $weightColumnExpression = '(' . implode(') + (', $weightColumnExpression) . ')';
        $this->getSelect()->where(new \Zend_Db_Expr($weightColumnExpression));

        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addStoreFilter()
    {
        $stores = [0];

        $store = $this->storeManager->getStore();
        if ($store instanceof \Magento\Store\Model\Store) {
            $stores[] = $store->getId();
        }

        $this->getSelect()
            ->join(
                ['store_table' => $this->getTable('ms_recipe_store')],
                'main_table.recipe_id = store_table.recipe_id',
                []
            )
            ->where('store_table.store_id IN (?)', $stores);

        return $this;
    }

    /**
     * @return $this
     */
    public function addIsEnableFilter()
    {
        $this->addFieldToFilter('status', 1);

        return $this;
    }

    /**
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        if ($this->storeId === null) {
            $this->storeId = $this->storeManager->getStore()->getId();
        }

        return $this->storeId;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function joinRecipeData()
    {
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
                    AND at_{$fieldName}_store.store_id = {$this->getStoreId()}",
                    null
                )
                ->columns([
                    $fieldName => new \Zend_Db_Expr(
                        "IFNULL(at_{$fieldName}_store.meta_value, at_{$fieldName}.meta_value)"
                    )
                ]);
        }

        return $this;
    }

    public function getCollectionById($ids)
    {
        $inCond = [];
        if (isset($ids)) {
            $inCond = $this->getSelect()
            ->where('main_table.recipe_id IN (?)', $ids);
        }

        return $inCond;
    }

    /**
     * @param $ids
     * @return array|\Magento\Framework\DB\Select
     */
    public function getCollectionByExcludedId($ids)
    {
        $inCond = [];
        if (isset($ids)) {
            $inCond = $this->getSelect()
                ->where('main_table.recipe_id NOT IN (?)', $ids);
        }

        return $inCond;
    }

    public function addWriterId()
    {
        $this->getSelect()
            ->join(
                ['writer' => $this->getTable('ms_recipe_writer')],
                'main_table.customer_id = writer.customer_id',
                ['writer_id'=>'id']
            );

        return $this;
    }

    /**
     * @param $productId
     * @return $this
     */
    public function skippRecipesWithProductId($productId)
    {
        $inCond = $this->getConnection()
            ->prepareSqlCondition(
                'product.product_id',
                ['neq' => $productId]
            );

        $this->getSelect()->join(
            ['product' => $this->getTable('ms_recipe_product')],
            'main_table.recipe_id = product.recipe_id AND ' . $inCond,
            []
        )->group('main_table.recipe_id');

        return $this;
    }
}
