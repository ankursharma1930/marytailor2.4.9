<?php
namespace Magebees\Productfeed\Controller\Adminhtml\Mapping;

class Grid extends \Magento\Backend\App\Action
{

    public function execute()
    {
        
            $this->getResponse()->setBody(
                $this->_view->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Mapping\Grid')->toHtml()
            );
    }
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::mapping');
    }
}
