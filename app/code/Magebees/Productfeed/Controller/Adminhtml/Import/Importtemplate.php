<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Controller\Adminhtml\Import;

class Importtemplate extends \Magebees\Productfeed\Controller\Adminhtml\Import
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;
    protected $dir;
    protected $filesystem;
    

    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                $data = $this->getRequest()->getPostValue();
                
                $inputFilter = new \Magento\Framework\Filter\FilterInput(
                    [],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();
                
                
                foreach ($data['import_templates'] as $key => $template_path) :
                     $template_content = file_get_contents($template_path);
                    if ($this->isJSON($template_content)) {
                        $template_content = json_decode($template_content, true);
                        unset($template_content['id']);
                        $templates = $this->_objectManager->create('Magebees\Productfeed\Model\Templates');
                        $templates->setData($template_content);
                        $templates->save();
                    }
                    $this->messageManager->addSuccess(__('Import '.$template_content['name'].' successfully.'));
                endforeach;
                 $this->_redirect('magebees_productfeed/templates/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                 $this->_redirect('magebees_productfeed/templates/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __($e->getMessage())
                );
                
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('magebees_productfeed/templates/');
                return;
            }
        }
        $this->_redirect('magebees_productfeed/templates/');
    }
    protected function _isAllowed()
    {
        return true;
    }
    public function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
