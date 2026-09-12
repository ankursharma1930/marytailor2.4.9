<?php namespace Magebees\Productfeed\Block\Adminhtml\Mapping;

class Content extends \Magento\Backend\Block\Template
{
    protected $_categoryCollectionFactory;
    protected $_categoryHelper;
    protected $_mapping;
    protected $_category;
    protected $_request;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magebees\Productfeed\Model\Mapping $mapping,
        \Magento\Catalog\Model\Category $category,
        array $data = []
    ) {
    
        $this->_category = $category;
        $this->_request = $request;
        $this->_mapping = $mapping;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_categoryHelper = $categoryHelper;
        parent::__construct($context, $data);
    }
    public function getCategoryCollection($isActive = true, $level = false, $sortBy = false, $pageSize = false)
    {
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        
        // select only active categories
        if ($isActive) {
            $collection->addIsActiveFilter();
        }
                
        // select categories of certain level
        if ($level) {
            $collection->addLevelFilter($level);
        }
        
        // sort categories by some value
        if ($sortBy) {
            $collection->addOrderField($sortBy);
        }
        
        // select certain number of categories
        if ($pageSize) {
            $collection->setPageSize($pageSize);
        }
        
        return $collection;
    }
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted = false, $asCollection = false, $toLoad = true);
    }
    public function getMapping()
    {
        return $this->_mapping;
    }
    public function getCategory()
    {
        return $this->_category;
    }
    public function getStoreId()
    {
    
        $param=$this->_request->getParams();
        $store_id=$param['store_id'];
        return $store_id;
    }
}
