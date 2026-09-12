<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Feed;
class NewAction extends \Magebees\Productfeed\Controller\Adminhtml\Feed
{

    public function execute()
    {

        $this->_forward('edit');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
