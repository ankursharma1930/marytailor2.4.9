<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Controller\Adminhtml\Templates;

class Importtemplate extends \Magebees\Productfeed\Controller\Adminhtml\Templates
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;
    protected $dir;
    protected $filesystem;
    
   
 
    public function execute()
    {
            $this->_initAction();
            $this->_view->getLayout()->getBlock('templates_templates_import');
            $this->_view->renderLayout();
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::templates');
    }
}
