<?php
namespace Magebees\Productfeed\Controller\Adminhtml\Templates;

class Grid extends \Magento\Backend\App\Action
{

    public function execute()
    {
        
            $this->getResponse()->setBody(
                $this->_view->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Templates\Grid')->toHtml()
            );
    }
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::templates');
    }
}
