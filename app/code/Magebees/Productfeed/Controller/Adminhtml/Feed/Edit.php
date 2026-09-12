<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Feed;

class Edit extends \Magebees\Productfeed\Controller\Adminhtml\Feed
{

    public function execute()
    {

        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Magebees\Productfeed\Model\Feed');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This feed no longer exists.'));
                $this->_redirect('magebees_productfeed/*');
                return;
            }
        }

        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $this->_coreRegistry->register('current_magebees_productfeed_feed', $model);
		
		if((!$model->getContent()) && ($model->getId())){
			if($model->getType()=='xml') {		
				$feed_content = json_decode($model->getContent(),true);
				if(!$feed_content['xml_body'])
				{
				$this->messageManager->addWarning(__('Please Set Feed Content And Save Feed For Generate Feed .'));
				}

			}else{
				$this->messageManager->addWarning(__('Please Set Feed Content And Save Feed For Generate Feed .'));
			}
		}
		
        //$this->_initAction();
		$this->_view->loadLayout();
        $this->_view->getLayout()->getBlock('productfeed_feed_edit');
        $this->_view->renderLayout();
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
