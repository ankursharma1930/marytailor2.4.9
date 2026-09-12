<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Mapping;

class Index extends \Magebees\Productfeed\Controller\Adminhtml\Mapping
{
    /**
     * Mapping list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magebees_Productfeed::productfeed');
        $resultPage->getConfig()->getTitle()->prepend(__('Magebees Mapping'));
        $resultPage->addBreadcrumb(__('Magebees'), __('Magebees'));
        $resultPage->addBreadcrumb(__('Mapping'), __('Mapping'));
        return $resultPage;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::mapping');
    }
}
