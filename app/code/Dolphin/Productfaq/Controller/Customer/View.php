<?php
namespace Dolphin\Productfaq\Controller\Customer;

use Magento\Framework\App\Action\Action;

class View extends Action
{
    /**
     * Customer FAQ Index Page Action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
