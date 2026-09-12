<?php

namespace Dolphin\Productfaq\Controller\Adminhtml\Productfaq;

use Dolphin\Productfaq\Controller\Adminhtml\Productfaq;
use Dolphin\Productfaq\Model\Productfaq as ProductfaqModel;
use Magento\Framework\Controller\ResultInterface;

class Delete extends Productfaq
{
    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Delete action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('productfaq_id');
        if ($id) {
            try {
                $model = $this->_objectManager->create(ProductfaqModel::class);
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('You deleted the Question.'));
                
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                
                return $resultRedirect->setPath('*/*/edit', ['productfaq_id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find a Productfaq to delete.'));

        return $resultRedirect->setPath('*/*/');
    }
}
