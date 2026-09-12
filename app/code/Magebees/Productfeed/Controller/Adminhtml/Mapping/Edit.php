<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Mapping;

class Edit extends \Magebees\Productfeed\Controller\Adminhtml\Mapping
{

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Magebees\Productfeed\Model\Mapping');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This mapping no longer exists.'));
                $this->_redirect('magebees_productfeed/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $this->_coreRegistry->register('current_magebees_productfeed_mapping', $model);
        $this->_initAction();
        $this->_view->getLayout()->getBlock('mapping_mapping_edit');
        
        $this->_view->renderLayout();
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::mapping');
    }
}
