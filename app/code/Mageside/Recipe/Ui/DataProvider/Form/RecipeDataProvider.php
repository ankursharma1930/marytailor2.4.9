<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Ui\DataProvider\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Mageside\Recipe\Model\FileUploader;
use Mageside\Recipe\Model\RecipeFactory;
use Mageside\Recipe\Model\ResourceModel\Recipe\Collection;
use Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Collection as FilterCollection;
use Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options\CollectionFactory;

class RecipeDataProvider extends AbstractDataProvider
{
    protected $availableImages = ['thumbnail', 'media_type_image'];

    /**
     * @var PoolInterface
     */
    protected $_pool;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var FileUploader
     */
    protected $_fileUploader;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Collection
     */
    protected $filterCollection;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options\CollectionFactory
     */
    protected $optionCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var RecipeFactory
     */
    protected $recipeFactory;

    /**
     * @var \Mageside\Recipe\Model\Recipe
     */
    protected $currentRecipe;

    /**
     * @var \Mageside\Recipe\Helper\Settings
     */
    protected $settingsHelper;

    /**
     * RecipeDataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Collection $collection
     * @param RecipeFactory $recipeFactory
     * @param PoolInterface $pool
     * @param RequestInterface $request
     * @param FileUploader $fileUploader
     * @param Registry $registry
     * @param FilterCollection $filterCollection
     * @param CollectionFactory $optionCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ArrayManager $arrayManager
     * @param \Mageside\Recipe\Helper\Settings $settingsHelper
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Collection $collection,
        \Mageside\Recipe\Model\RecipeFactory $recipeFactory,
        PoolInterface $pool,
        RequestInterface $request,
        FileUploader $fileUploader,
        Registry $registry,
        FilterCollection $filterCollection,
        CollectionFactory $optionCollectionFactory,
        StoreManagerInterface $storeManager,
        ArrayManager $arrayManager,
        \Mageside\Recipe\Helper\Settings $settingsHelper,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collection;
        $this->_pool = $pool;
        $this->recipeFactory = $recipeFactory;
        $this->_request = $request;
        $this->_fileUploader = $fileUploader;
        $this->registry = $registry;
        $this->filterCollection = $filterCollection;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->storeManager = $storeManager;
        $this->arrayManager = $arrayManager;
        $this->settingsHelper = $settingsHelper;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = $this->meta;
        $meta = $this->prepareFilterFields($meta);

        /** @var ModifierInterface $modifier */
        foreach ($this->_pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        $meta = $this->prepareFields($meta);
        $meta = $this->prepareSearchEngineFields($meta);
        $meta = $this->prepareCookingFields($meta);

        return $meta;
    }

    /**
     * @param $meta
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function prepareFilterFields($meta)
    {
        $fields = [];
        $filters = $this->filterCollection->joinOptionData()->getItems();
        foreach ($filters as $filter) {
            $fields[$filter->getCode()] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label'         => $filter->getType(),
                            'componentType' => 'field',
                            'options'       => $this->getFilterOptions($filter->getId()),
                            'component'     => 'Mageside_Recipe/js/components/form/multiselect',
                            'formElement'   => 'multiselect',
                            'dataScope'     => 'data.recipe.' . $filter->getCode()
                        ]
                    ]
                ]
            ];
        }

        $meta = array_replace_recursive(
            $meta,
            [
                'recipe_form' => [
                    'children' => $fields,
                ]
            ]
        );

        return $meta;
    }

    /**
     * @param $meta
     * @return mixed
     */
    protected function prepareSearchEngineFields($meta)
    {
        /** @var \Mageside\Recipe\Model\Recipe $recipe */
        $recipe = $this->getCurrentRecipe();
        $data = $recipe->getData();

        $joinFields = $this->settingsHelper->getSearchEngineFields();
        foreach ($joinFields as $name => $inputType) {
            if ((isset($data[$name . '_is_default'])) && ($this->getCurrentStoreId())) {
                $useDefaultConfig = [
                    'formElement'   => $inputType,
                    'componentType' => 'field',
                    'usedDefault'   => $data[$name . '_default_value'] ? true : false,
                    'disabled'      => $data[$name . '_is_default'] ? true : false,
                    'service'       => [
                        'template'  => 'ui/form/element/helper/service',
                    ]
                ];
                $meta['search_engine_optimisation']['children'][$name]['arguments']['data']['config'] = $useDefaultConfig;
            }
        }

        return $meta;
    }

    /**
     * @param $meta
     * @return mixed
     */
    protected function prepareFields($meta)
    {
        /** @var \Mageside\Recipe\Model\Recipe $recipe */
        $recipe = $this->getCurrentRecipe();
        $data = $recipe->getData();

        $joinFields = $this->settingsHelper->getStoreDefinedFields();
        foreach ($joinFields as $name => $inputType) {
            if ((isset($data[$name . '_is_default'])) && ($this->getCurrentStoreId())) {
                $useDefaultConfig = [
                    'formElement'   => $inputType,
                    'componentType' => 'field',
                    'usedDefault'   => $data[$name . '_default_value'] ? true : false,
                    'disabled'      => $data[$name . '_is_default'] ? true : false,
                    'service'       => [
                        'template'  => 'ui/form/element/helper/service',
                    ]
                ];
                $meta['recipe_form']['children'][$name]['arguments']['data']['config'] = $useDefaultConfig;
            }
        }

        return $meta;
    }

    /**
     * @param $meta
     * @return mixed
     */
    protected function prepareCookingFields($meta)
    {
        /** @var \Mageside\Recipe\Model\Recipe $recipe */
        $recipe = $this->getCurrentRecipe();
        $data = $recipe->getData();

        $cookingFields = $this->settingsHelper->getStoreCookingFields();
        foreach ($cookingFields as $container => $fields) {
            if ((isset($data[$container . '_is_default'])) && ($this->_request->getParam('store'))) {
                $useDefaultConfig = [
                    'use_default'   => $data[$container . '_default_value'] ? true : false,
                    'usedDefault'   => $data[$container . '_default_value'] ? true : false,
                    'disabled'      => $data[$container . '_is_default'] ? true : false,
                    'uid'           => $container,
                    'service'       => [
                        'template'  => 'ui/form/element/helper/service',
                    ],
                ];
                $meta["cooking"]["children"][$container]["arguments"]["data"]["config"] = $useDefaultConfig;
                foreach ($fields as $field) {
                    $meta["cooking"]["children"][$container]
                    ["children"]["option_" . $container]
                    ["children"][$field]["arguments"]
                    ["data"]["config"]
                    ["imports"]["disabled"] = '${$.namespace}.${$.namespace}.cooking.' . $container . ':isUseDefault';
                }
            }
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        // TODO: needs to be refactored
        $recipe = $this->collection
            ->setStoreId($this->getCurrentStoreId())
            ->joinRecipeData()
            ->setPageSize(1)
            ->setCurPage(1)
            ->getFirstItem();

        if ($recipe && $recipe->getId()) {
            $this->data[$recipe->getId()]['recipe'] = $this->prepareData($recipe);
        }

        $customerId = $this->_request->getParam('customer_id');
        if ($customerId) {
            if ($recipe->getId()) {
                $this->data[$recipe->getId()]['recipe']['customer_id'] = $customerId;
            } else {
                $this->data['config']['data']['recipe']['customer_id'] = $customerId;
            }
        }

        /** @var ModifierInterface $modifier */
        foreach ($this->_pool->getModifiersInstances() as $modifier) {
            $this->data = $modifier->modifyData($this->data);
        }

        return $this->data;
    }

    /**
     * TODO: needs to be refactored (filters data not loading)
     *
     * @return \Magento\Framework\DataObject|\Mageside\Recipe\Model\Recipe
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCurrentRecipe()
    {
        if (!$this->currentRecipe) {
            $recipe = $this->recipeFactory->create();
            $recipe->setStoreId($this->getCurrentStoreId());
            $this->currentRecipe = $recipe->load($this->_request->getParam('recipe_id'));
        }

        return $this->currentRecipe;
    }

    /**
     * @param $filterItem
     * @return mixed
     */
    protected function prepareData($filterItem)
    {
        $prepareOption = $filterItem->toArray();
        foreach ($this->availableImages as $imageName) {
            if (!empty($prepareOption[$imageName])) {
                $imageData = [
                    'name' => $prepareOption[$imageName],
                    'url' =>  $this->_fileUploader->getFileWebUrl($prepareOption[$imageName])
                ];
                $prepareOption[$imageName] = [];
                $prepareOption[$imageName][0] = $imageData;
            }
        }

        if (!empty($prepareOption['options'])) {
            foreach ($prepareOption['options'] as $filterName => $filterData) {
                foreach ($filterData as $filterOptionId) {
                    $prepareOption[$filterName][] = $filterOptionId['option_id'];
                }
            }
        }

        return $prepareOption;
    }

    /**
     * @param $filterId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFilterOptions($filterId)
    {
        $filterCollection = $this->optionCollectionFactory->create();
        $filterCollection = $filterCollection->joinOptionData($filterId)->getItems();
        $option = [];
        foreach ($filterCollection as $filterOption) {
            $option[] = [
                'label' => $filterOption->getLabel(),
                'value' => $filterOption->getId()
            ];
        }

        return $option;
    }

    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getCurrentStoreId()
    {
        if (($this->_request->getParam('store') != null)) {
            $currentStoreId = $this->_request->getParam('store');
        } else {
            $currentStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        return $currentStoreId;
    }
}
