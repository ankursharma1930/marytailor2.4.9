<?php namespace Magebees\Productfeed\Block\Adminhtml\Feed;

class Category extends \Magento\Backend\Block\Template
{
    protected $_categoryCollectionFactory;
    protected $_categoryHelper;
    protected $_feed;
    protected $_categoryFactory;
    protected $_category;
    protected $_request;
   
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\Category $category,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magebees\Productfeed\Model\Feed $feed,
        array $data = []
    ) {
    
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_categoryHelper = $categoryHelper;
        $this->_request = $request;
        $this->_feed = $feed;
        $this->_category = $category;
        $this->_categoryFactory = $categoryFactory;
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
    public function getCategory()
    {
        return $this->_categoryFactory;
    }
    public function getCategoryHelper()
    {
        return $this->_category;
    }
    public function getFeed()
    {
        return $this->_feed;
    }
    public function getStoreId()
    {
        
        
        $param=$this->_request->getParams();
        $store_id=$param['store_id'];
        return $store_id;
    }
}
