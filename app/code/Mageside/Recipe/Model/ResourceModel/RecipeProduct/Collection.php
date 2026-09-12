<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\RecipeProduct;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var  \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * Catalog product visibility
     *
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $catalogProductVisibility;

    /**
     * Catalog config
     *
     * @var \Magento\Catalog\Model\Config
     */
    protected $catalogConfig;

    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->catalogConfig = $catalogConfig;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Mageside\Recipe\Model\RecipeProduct::class,
            \Mageside\Recipe\Model\ResourceModel\RecipeProduct::class
        );
    }

    /**
     * @param $recipeId
     * @return Collection
     */
    public function addRecipeFilter($recipeId)
    {
        return $this->addFieldToFilter('recipe_id', $recipeId);
    }

    /**
     * @return array
     */
    public function getProductIds()
    {
        $ids = [];
        foreach ($this->getData() as $item) {
            $ids[] = $item['product_id'];
        }

        return $ids;
    }

    /**
     * @param null $storeId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection($storeId = null)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productCollectionFactory->create();

        if ($storeId !== 0) {
            $collection->addStoreFilter($storeId)
                ->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds())
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents();
        }

        $collection->addFieldToFilter('entity_id', ['in' => $this->getProductIds()])
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes());

        $items = $this->getData();
        $products = $collection->getItems();
        foreach ($items as $item) {
            if (isset($products[$item['product_id']])) {
                $products[$item['product_id']]->setData('qty', $item['qty']);
            }
        }

        return $collection;
    }
}
