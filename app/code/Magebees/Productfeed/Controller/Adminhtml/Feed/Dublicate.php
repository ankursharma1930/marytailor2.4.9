<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Feed;
use Magento\Framework\App\Config\ScopeConfigInterface;
class Dublicate extends \Magento\Backend\App\Action
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;
    protected $_dir = null;
    protected $filesystem;
    protected $scopeConfig;
    
    //protected $_product_data_collection=array();
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Filesystem $filesystem,
		ScopeConfigInterface $scopeConfig
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_dir = $dir;
		 $this->scopeConfig = $scopeConfig;
        $this->_filesystem = $filesystem;
        parent::__construct($context);
    }
 
    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $model = $this->_objectManager->create('Magebees\Productfeed\Model\Feed');
            $dublicatefeed = $this->_objectManager->create('Magebees\Productfeed\Model\Feed');
            $filesystem = $this->_objectManager->get('Magento\Framework\Filesystem');
            $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
            $scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
					if(!empty($scope_info['file_path']))
					{
					$file_path = $scope_info['file_path'];
					}else{
					$file_path = "var/export/";
					}
			
			$path = $reader->getAbsolutePath($file_path);
            
            if ($id) {
                $model->load($id);
                if ($id != $model->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong feed is specified.'));
                }
            }
                
            $data = $model->getData();
            if (isset($data['id'])) {
                unset($data['id']);
            }
            if (isset($data['name'])) {
                $data['name'] = $data['name']."-dublicate";
            }
            if (isset($data['download_url'])) {
                $download_url_info = $data['download_url'];
                if ($this->isJSON($download_url_info)) {
                    $download_url_info = json_decode($download_url_info, true);
                    $path_info = pathinfo($path.$download_url_info['feedname']);
                    if (isset($path_info['filename'])) {
                        $oldfile = $path_info['filename'].".".$path_info['extension'];
                        $new_file = $path_info['filename']."-dublicate".".".$path_info['extension'];
                        if (file_exists($path.$oldfile)) {
                            if (!file_exists($path.$new_file)) {
                                $content="";
                                $fp = fopen($path.$new_file, "w");
                                fwrite($fp, $content);
                                fclose($fp);
                            }
                            $download_url_info['feedstatus'] = "1";
                            if (!copy($path.$oldfile, $path.$new_file)) {
                                $download_url_info['feedstatus'] = "0";
                            }
                        } else {
                            $download_url_info['feedstatus'] = "0";
                        }
                            
                        $download_url_info['feedname'] = $new_file;
                    } else {
                        $download_url_info['feedstatus'] = "0";
                    }
                }
                $data['download_url'] = json_encode($download_url_info);
            }
            $dublicatefeed->setData($data);
            $dublicatefeed->save();
            $this->messageManager->addSuccess(__('You created dublicate feed.'));
            $this->_redirect('magebees_productfeed/*/');
            return;
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
    public function isJSON($string)
    {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
