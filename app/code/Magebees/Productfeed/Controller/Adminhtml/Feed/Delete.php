<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Feed;

class Delete extends \Magebees\Productfeed\Controller\Adminhtml\Feed
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
               $scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
				
				$path = $reader->getAbsolutePath($file_path);
                $model = $this->_objectManager->create('Magebees\Productfeed\Model\Feed');
                $model->load($id);
                
                if ($this->isJSON($model->getDownloadUrl())) {
                    $download_url_info = json_decode($model->getDownloadUrl(), true);
                    if (file_exists($path.$download_url_info['feedname'])) {
                        unlink($path.$download_url_info['feedname']);
                    }
                }
                
                
                $model->delete();
                $this->messageManager->addSuccess(__('You deleted the Feed.'));
                $this->_redirect('magebees_productfeed/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete feed right now. Please review the log and try again.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_redirect('magebees_productfeed/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a feed to delete.'));
        $this->_redirect('magebees_productfeed/*/');
    }
    public function isJSON($string)
    {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
