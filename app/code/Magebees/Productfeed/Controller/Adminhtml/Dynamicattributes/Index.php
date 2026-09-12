<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Dynamicattributes;

class Index extends \Magebees\Productfeed\Controller\Adminhtml\Dynamicattributes
{
    /**
     * Dynamicattributes list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magebees_Productfeed::productfeed');
        $resultPage->getConfig()->getTitle()->prepend(__('Dynamic Attributes'));
        $resultPage->addBreadcrumb(__('Magebees'), __('Magebees'));
        $resultPage->addBreadcrumb(__('Dynamicattributes'), __('Dynamic Attributes'));
        return $resultPage;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::dynamicattributes');
    }
}
