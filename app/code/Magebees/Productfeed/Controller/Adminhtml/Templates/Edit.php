<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Templates;

class Edit extends \Magebees\Productfeed\Controller\Adminhtml\Templates
{

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Magebees\Productfeed\Model\Templates');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This template no longer exists.'));
                $this->_redirect('magebees_productfeed/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $this->_coreRegistry->register('current_magebees_productfeed_templates', $model);
        $this->_initAction();
        $this->_view->getLayout()->getBlock('templates_templates_edit');
        $this->_view->renderLayout();
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::templates');
    }
}
