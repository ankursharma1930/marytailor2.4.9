<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Import;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Templates list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected $resultPageFactory;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magebees_Productfeed::productfeed');
        $resultPage->getConfig()->getTitle()->prepend(__('Magebees Import'));
        $resultPage->addBreadcrumb(__('Magebees'), __('Magebees'));
        $resultPage->addBreadcrumb(__('Import'), __('Import'));
        return $resultPage;
    }
    protected function _isAllowed()
    {
        return true;
    }
}
