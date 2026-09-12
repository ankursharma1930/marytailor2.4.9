<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Mapping;

class Delete extends \Magebees\Productfeed\Controller\Adminhtml\Mapping
{

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $model = $this->_objectManager->create('Magebees\Productfeed\Model\Mapping');
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('You deleted the Mapping.'));
                $this->_redirect('magebees_productfeed/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete mapping right now. Please review the log and try again.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_redirect('magebees_productfeed/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a mapping to delete.'));
        $this->_redirect('magebees_productfeed/*/');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::mapping');
    }
}
