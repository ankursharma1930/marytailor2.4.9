<?php
namespace Magebees\Productfeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Downloadexportedfile extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    protected $_filesystem;	protected $filesystem;
    protected $_scopeConfig;
    
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Filesystem $filesystem,
		\Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig,
		PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_filesystem = $filesystem;
		$this->_scopeConfig = $_scopeConfig;
        $this->resultPageFactory = $resultPageFactory;
    }
    
    public function execute()
    {

        /* Download File From the Grid Download Link & Edit Feed Url Link.*/
        try {
            $filename=$this->getRequest()->getParam('file');
            $reader =   $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
			$scope_info = $this->_scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
			
            $filepath = $reader->getAbsolutePath($file_path.$filename);
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Content-type', 'application/force-download')
                ->setHeader('Content-Length', filesize($filepath))
                ->setHeader('Content-Disposition', 'attachment' . '; filename=' . basename($filepath));
            $this->getResponse()->clearBody();
            $this->getResponse()->sendHeaders();
            readfile($filepath);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('*/*/');
            return;
        } catch (\Exception $e) {
            $this->messageManager->addError(
                __('Something went wrong while download the feed file. Please review the error log.')
            );
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            $this->_redirect('*/*/');
            return;
        }
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
