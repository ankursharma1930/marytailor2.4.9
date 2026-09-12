<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Controller\Adminhtml\Templates;

class Dublicate extends \Magento\Backend\App\Action
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;
    
    
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        
        parent::__construct($context);
    }
 
    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $model = $this->_objectManager->create('Magebees\Productfeed\Model\Templates');
            $dublicatetemplate = $this->_objectManager->create('Magebees\Productfeed\Model\Templates');
                
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
            $dublicatetemplate->setData($data);
            $dublicatetemplate->save();
            $this->messageManager->addSuccess(__('You created dublicate template.'));
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
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::templates');
    }
}
