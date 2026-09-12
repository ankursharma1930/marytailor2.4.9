<?php

namespace Magebees\Productfeed\Controller\Adminhtml\Templates;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;

class MassDelete extends \Magento\Backend\App\Action
{
   
    public function execute()
    {
        $templateIds = $this->getRequest()->getParam('template_ids');
        
        if (!is_array($templateIds) || empty($templateIds)) {
            $this->messageManager->addError(__('Please select Templates(s).'));
        } else {
            try {
                foreach ($templateIds as $templateId) {
                    $model = $this->_objectManager->get('Magebees\Productfeed\Model\Templates')->load($templateId);
                    $model->delete();
                }
                        
                    $this->messageManager->addSuccess(
                        __('A total of %1 record(s) have been deleted.', count($templateIds))
                    );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
         $this->_redirect('*/*/');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::templates');
    }
}
