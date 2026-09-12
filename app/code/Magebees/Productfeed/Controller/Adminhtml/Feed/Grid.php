<?php
namespace Magebees\Productfeed\Controller\Adminhtml\Feed;

class Grid extends \Magento\Backend\App\Action
{

    public function execute()
    {
        
            $this->getResponse()->setBody(
                $this->_view->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Feed\Grid')->toHtml()
            );
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
