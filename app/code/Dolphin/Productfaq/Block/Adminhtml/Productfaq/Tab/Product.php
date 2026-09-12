<?php

namespace Dolphin\Productfaq\Block\Adminhtml\Productfaq\Tab;

use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Framework\Registry;
use Dolphin\Productfaq\Model\ResourceModel\Products\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;

class Product extends Extended
{
    /**
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var CollectionFactory
     */
    protected $proCollectionFactory;

    /**
     * @var ProductCollection
     */
    protected $productCollectionFactory;
    /**
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $proCollectionFactory
     * @param ProductCollection $productCollectionFactory
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $proCollectionFactory,
        ProductCollection $productCollectionFactory,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->proCollectionFactory = $proCollectionFactory;
        $this->coreRegistry = $coreRegistry;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Construct Function
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('catalog_category_products');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    /**
     * Get Product
     *
     * @return array
     */
    public function getProduct()
    {
        return $this->coreRegistry->registry('productfaq');
    }

    /**
     * Add Column Filter To Collection
     *
     * @param array $column
     * @return array
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_product') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Prepare collection
     */
    protected function _prepareCollection()
    {
        /** to get default filter in grid */
        if ($this->getRequest()->getParam('productfaq_id')) {
            $this->setDefaultFilter(['in_product' => 1]);
        }
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('price');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Preaper Columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_product',
            [
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_product',
                'align' => 'center',
                'index' => 'entity_id',
                'values' => $this->_getSelectedProducts(),
            ]
        );
        $this->addColumn(
            'entity_id',
            [
                'header' => __('Product ID'),
                'type' => 'number',
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'names',
            [
                'header' => __('Name'),
                'index' => 'name',
                'class' => 'xxx',
                'width' => '50px',
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('Sku'),
                'index' => 'sku',
                'class' => 'xxx',
                'width' => '50px',
            ]
        );
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'index' => 'price',
                'width' => '50px',
            ]
        );
        return parent::_prepareColumns();
    }

    /**
     * Get Grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/products/grid', ['_current' => true]);
    }

    /**
     * Retrieve selected products associated with a product FAQ entry
     *
     * @return array Selected product IDs
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('selected_products');
        $productFaqId = $this->getRequest()->getParam('productfaq_id');
        if ($products === null) {
            $vProducts = $this->proCollectionFactory->create()
                ->addFieldToFilter('productfaq_id', $productFaqId)
                ->addFieldToSelect('product_id');
            $products = [];
            foreach ($vProducts as $pdct) {
                $products[] = $pdct->getProductId();
            }
        }
        return $products;
    }
}
