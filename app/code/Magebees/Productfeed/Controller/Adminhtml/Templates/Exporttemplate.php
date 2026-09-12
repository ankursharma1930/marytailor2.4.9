<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Controller\Adminhtml\Templates;

class Exporttemplate extends \Magento\Backend\App\Action
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;
    protected $dir;
    protected $filesystem;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->_filesystem = $filesystem;
        $this->_dir = $dir;
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        
        parent::__construct($context);
    }
 
    public function execute()
    {
            
            $filesystem = $this->_objectManager->get('Magento\Framework\Filesystem');
            $extvardir = $this->_filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
            $exportdir = '/export/feedtemplate';
            $extvardir->create($exportdir);
            $extvardir->changePermissions($exportdir, \Magento\Framework\Filesystem\DriverInterface::WRITEABLE_DIRECTORY_MODE);
            $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
            $path = $reader->getAbsolutePath('export/feedtemplate/');
            
            
        
        try {
            $id = $this->getRequest()->getParam('id');
            $exporttemplate = $this->_objectManager->create('Magebees\Productfeed\Model\Templates');
                
            if ($id) {
                $exporttemplate->load($id);
                if ($id != $exporttemplate->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong template is specified.'));
                }
                    
                $data = $exporttemplate->getData();
                $content = json_encode($data);
                $template_name = $exporttemplate->getName().".json";
                $fp = fopen($path.$template_name, 'w');
                fwrite($fp, $content);
                fclose($fp);
                $this->messageManager->addSuccess(__('Template "'.$exporttemplate->getName().'" has been exported to "'.$path.$template_name.'"'));
                $this->_redirect('magebees_productfeed/*/');
                return;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('The wrong template is specified.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('magebees_productfeed/*/');
            return;
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            $this->_redirect('magebees_productfeed/*/');
            return;
        }
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::templates');
    }
}
