<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Controller\Adminhtml\Import;

class Import extends \Magento\Backend\App\Action
{
    public function execute()
    {
             $this->_forward('importtemplate');
    }
}
