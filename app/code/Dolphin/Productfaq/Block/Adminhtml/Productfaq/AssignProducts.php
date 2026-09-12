<?php

namespace Dolphin\Productfaq\Block\Adminhtml\Productfaq;

use \Magento\Backend\Block\Template;
use \Magento\Backend\Block\Template\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\Json\EncoderInterface;
use \Dolphin\Productfaq\Model\ResourceModel\Products\CollectionFactory;
use \Dolphin\Productfaq\Block\Adminhtml\Productfaq\Tab\Product;

class AssignProducts extends Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'catalog/category/edit/assign_products.phtml';
    /**
     * @var \Magento\Catalog\Block\Adminhtml\Category\Tab\Product
     */
    protected $blockGrid;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * AssignProducts constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param EncoderInterface $jsonEncoder
     * @param CollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        EncoderInterface $jsonEncoder,
        CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->jsonEncoder = $jsonEncoder;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve instance of grid block
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                Product::class,
                'productfaq.product.grid'
            );
        }
        return $this->blockGrid;
    }
    /**
     * Return HTML of grid block
     *
     * @return string
     */
    public function getGridHtml()
    {

        return $this->getBlockGrid()->toHtml();
    }
    /**
     * Get Product Json
     *
     * @return string
     */
    public function getProductsJson()
    {
        $productFaqId = $this->getRequest()->getParam('productfaq_id');
        $vProducts = $this->productCollectionFactory->create()
            ->addFieldToFilter('productfaq_id', $productFaqId)
            ->addFieldToSelect('product_id');
        $products = [];
        foreach ($vProducts as $pdct) {
            $products[$pdct->getProductId()] = $pdct->getProductId();
        }
        if (!empty($products)) {
            return $this->jsonEncoder->encode($products);
        }
        return '{}';
    }
    /**
     * Get Product
     *
     * @return array
     */
    public function getProduct()
    {
        return $this->registry->registry('productfaq');
    }
}
