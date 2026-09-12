<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Mapping;

class NewAction extends \Magebees\Productfeed\Controller\Adminhtml\Mapping
{

    public function execute()
    {
        $this->_forward('edit');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::mapping');
    }
}
