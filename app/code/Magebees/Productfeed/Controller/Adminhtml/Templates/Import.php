<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Controller\Adminhtml\Templates;

class Import extends \Magento\Backend\App\Action
{
    public function execute()
    {
             $this->_forward('importtemplate');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::templates');
    }
}
