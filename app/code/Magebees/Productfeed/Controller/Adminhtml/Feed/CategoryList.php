<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Controller\Adminhtml\Feed;

use Magento\Framework\Controller\ResultFactory;

class CategoryList extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    protected $resultJsonFactory;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
    
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        /* Manage Feed Category Tab Section Set From Here*/
        $resultFactory = $this->resultPageFactory->create();
        $resultPage= $this->resultPageFactory->create();
        $layoutblk = $resultPage->addHandle('productfeed_feed_category')->getLayout();
        $search_content= $layoutblk->getBlock('productfeed_feed_categorylist')->toHtml();
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($search_content);
        return $resultJson;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
