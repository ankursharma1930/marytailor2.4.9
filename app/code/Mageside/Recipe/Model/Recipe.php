<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

class Recipe extends AbstractModel implements IdentityInterface
{
    /**
     * Recipe cache tag
     */
    const CACHE_TAG = 'recipe';
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     * @var string
     */
    protected $_eventPrefix = 'recipe';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ResourceModel\RecipeProduct\Collection
     */
    protected $recipeProductCollection;
    /**
     * @var
     */
    protected $assignedProducts;

    /**
     * @var string
     */
    protected $mainTable;

    /**
     * @var $storeId
     */
    protected $storeId;

    /**
     * @var \Mageside\Recipe\Helper\Settings
     */
    protected $settingsHelper;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * Recipe constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceModel\RecipeProduct\Collection $recipeProductCollection
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Mageside\Recipe\Helper\Settings $settingsHelper
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Mageside\Recipe\Model\ResourceModel\RecipeProduct\Collection $recipeProductCollection,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Mageside\Recipe\Helper\Settings $settingsHelper,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->recipeProductCollection = $recipeProductCollection;
        $this->storeManager = $storeManager;
        $this->settingsHelper = $settingsHelper;
        $this->filterProvider = $filterProvider;
        $this->mainTable = 'ms_recipe';
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(\Mageside\Recipe\Model\ResourceModel\Recipe::class);
    }

    /**
     * @return array
     */
    public function getAssignedProductIds()
    {
        return $this->getAssignedProducts()->getAllIds();
    }

    /**
     * @param null $storeId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getAssignedProducts($storeId = null)
    {
        if ($this->assignedProducts === null) {
            $recipeId = $this->getId();
            $this->assignedProducts = $this->recipeProductCollection
                ->addRecipeFilter($recipeId)
                ->getProductCollection($storeId);
        }

        return $this->assignedProducts;
    }

    /**
     * @param $recipesData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveRecipeData($recipesData)
    {
        if ($currentRecipeId = $this->getId()) {
            $currentStoreId = $this->getStoreId();
            $joinFields = $this->settingsHelper->getJoinFields();
            foreach ($joinFields as $fieldName => $backendType) {
                if (isset($recipesData['use_default'][$fieldName])) {
                    $this->updateRecipeData(
                        $backendType,
                        $fieldName,
                        $currentStoreId,
                        $currentRecipeId,
                        $recipesData['use_default'][$fieldName]
                    );
                } elseif (isset($this->_data[$fieldName])) {
                    $this->updateRecipeData(
                        $backendType,
                        $fieldName,
                        $currentStoreId,
                        $currentRecipeId,
                        '0'
                    );
                }
            }
        }
    }

    /**
     * @param $backendType
     * @param $fieldName
     * @param $currentStoreId
     * @param $currentRecipeId
     */
    private function updateRecipeData($backendType, $fieldName, $currentStoreId, $currentRecipeId, $selected)
    {
        $collection = $this->getCollection();
        $connection = $collection->getSelect()->getConnection();
        $tableName = $collection->getTable('ms_recipe_' . $backendType);

        $prepareCurrentData = $this->prepareFieldData($fieldName, $currentStoreId, $currentRecipeId);

        if ($this->isDataExistByStore($tableName, $fieldName, $currentStoreId, $currentRecipeId)) {
            if ($selected == '0') {
                $connection->update(
                    $tableName,
                    $prepareCurrentData,
                    "meta_key = \"{$fieldName}\" AND
                            store_id = " . $currentStoreId . " AND
                            recipe_id = " . $currentRecipeId
                );
            } else {
                $connection->delete(
                    $tableName,
                    "meta_key = \"{$fieldName}\" AND
                            store_id = " . $currentStoreId . " AND
                            recipe_id = " . $currentRecipeId
                );
            }
        } else {
            if ($selected == '0') {
                $connection->insert($tableName, $prepareCurrentData);
                if (!$this->isDataExistByStore($tableName, $fieldName, '0', $currentRecipeId)) {
                    $prepareCurrentData['store_id'] = '0';
                    $connection->insert($tableName, $prepareCurrentData);
                }
            }
        }
    }

    /**
     * @param $tableName
     * @param $prepareDate
     * @param $fieldName
     * @param $currentStoreId
     * @param $currentRecipeId
     * @return bool
     */
    private function isDataExistByStore($tableName, $fieldName, $currentStoreId, $currentRecipeId)
    {
        $collection = $this->getCollection();
        $connection = $collection->getSelect()->getConnection();
        $select = $connection->select()
            ->from($tableName)
            ->where('meta_key = ?', $fieldName)
            ->where('store_id = ?', $currentStoreId)
            ->where('recipe_id = ?', $currentRecipeId);

        return $connection->fetchRow($select) ? true : false;
    }

    /**
     * @param $fieldName
     * @param $currentStoreId
     * @param $currentRecipeId
     * @return array
     */
    private function prepareFieldData($fieldName, $currentStoreId, $currentRecipeId)
    {
        return [
            "meta_key" => "{$fieldName}",
            "store_id" => $currentStoreId,
            "recipe_id" => $currentRecipeId,
            "meta_value" => $this->_data[$fieldName]
        ];
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
     * @param $currentStoreId
     * @param $currentRecipeId
     * @return bool
     */
    public function isRecipeExistByStore($currentStoreId, $currentRecipeId)
    {
        $collection = $this->getCollection();
        $tableName = $collection->getTable('ms_recipe_store');
        $connection = $collection->getSelect()->getConnection();
        $select = $connection->select()
            ->from($tableName)
            ->where('store_id IN (?,0)', $currentStoreId)
            ->where('recipe_id = ?', $currentRecipeId);

        return $connection->fetchRow($select) ? true : false;
    }

    /**
     * Get identities
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG, self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getShortDescriptionHtml()
    {
        return $this->filterProvider->getBlockFilter()->setStoreId($this->getStoreId())->filter($this->getShortDescription());
    }
}
