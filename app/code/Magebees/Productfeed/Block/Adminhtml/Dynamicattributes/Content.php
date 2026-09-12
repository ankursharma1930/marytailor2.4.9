<?php namespace Magebees\Productfeed\Block\Adminhtml\Dynamicattributes;

class Content extends \Magento\Backend\Block\Template
{
    
    protected $_templates;
    protected $_dynamicattributes;
    protected $_dynamicattributesResource;
    
    protected $_category;
    protected $_catalogProduct;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magebees\Productfeed\Model\Dynamicattributes $dynamicattributes,
        \Magebees\Productfeed\Model\ResourceModel\Dynamicattributes $dynamicattributesResource,
        \Magento\Catalog\Model\ResourceModel\Product $catalogProduct,
        \Magebees\Productfeed\Model\Templates $templates,
        array $data = []
    ) {
    
        $this->_dynamicattributes = $dynamicattributes;
        $this->_dynamicattributesResource = $dynamicattributesResource;
        $this->_templates = $templates;
        $this->_catalogProduct = $catalogProduct;
        parent::__construct($context, $data);
    }
    
    public function getTemplates()
    {
        return $this->_templates;
    }
    public function getgetDynamicattributesResource()
    {
        return $this->_dynamicattributesResource;
    }
    public function getDynamicattributes()
    {
        return $this->_dynamicattributes;
    }
    public function getcatalogProduct()
    {
        return $this->_catalogProduct;
    }
}
