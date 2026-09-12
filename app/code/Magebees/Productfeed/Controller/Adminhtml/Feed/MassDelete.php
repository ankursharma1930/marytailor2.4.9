<?php
namespace Magebees\Productfeed\Controller\Adminhtml\Feed;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class MassDelete extends \Magento\Backend\App\Action
{
   
    protected $_dir = null;
    protected $filesystem;
    protected $scopeConfig;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Filesystem\DirectoryList $dir,
		ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem
    ) {
        
        $this->_dir = $dir;
        $this->_filesystem = $filesystem;
		$this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    public function execute()
    {
        $feedIds = $this->getRequest()->getParam('feed_ids');
        
        $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
		$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
		
        $path = $reader->getAbsolutePath($file_path);
        
        if (!is_array($feedIds) || empty($feedIds)) {
            $this->messageManager->addError(__('Please select Feed(s).'));
        } else {
            try {
                foreach ($feedIds as $feedId) {
                    $model = $this->_objectManager->get('Magebees\Productfeed\Model\Feed')->load($feedId);
                    if ($this->isJSON($model->getDownloadUrl())) {
                        $download_url_info = json_decode($model->getDownloadUrl(), true);
                        if (file_exists($path.$download_url_info['feedname'])) {
                            unlink($path.$download_url_info['feedname']);
                        }
                    }
                    $model->delete();
                }
                        
                    $this->messageManager->addSuccess(
                        __('A total of %1 record(s) have been deleted.', count($feedIds))
                    );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
         $this->_redirect('*/*/');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
    public function isJSON($string)
    {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
