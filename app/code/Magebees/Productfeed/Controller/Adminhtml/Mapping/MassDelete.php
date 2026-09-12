<?php

namespace Magebees\Productfeed\Controller\Adminhtml\Mapping;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;

class MassDelete extends \Magento\Backend\App\Action
{
   
    public function execute()
    {
        $mappingIds = $this->getRequest()->getParam('mapping_ids');
        
        if (!is_array($mappingIds) || empty($mappingIds)) {
            $this->messageManager->addError(__('Please select Mapping(s).'));
        } else {
            try {
                foreach ($mappingIds as $mappingId) {
                    $model = $this->_objectManager->get('Magebees\Productfeed\Model\Mapping')->load($mappingId);
                    $model->delete();
                }
                        
                    $this->messageManager->addSuccess(
                        __('A total of %1 record(s) have been deleted.', count($mappingIds))
                    );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
         $this->_redirect('*/*/');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::mapping');
    }
}
