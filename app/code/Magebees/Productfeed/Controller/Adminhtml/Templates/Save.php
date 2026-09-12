<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Templates;

class Save extends \Magento\Backend\App\Action
{
    protected $resultJsonFactory;
      /**
       * Constructor
       *
       * @param \Magento\Backend\App\Action\Context $context
       * @param \Magento\Framework\Cache\FrontendInterface $attributeLabelCache
       * @param \Magento\Framework\Registry $coreRegistry
       * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
       * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
       */
   
    public function execute()
    {
        
        if ($this->getRequest()->getPostValue()) {
            try {
                $model = $this->_objectManager->create('Magebees\Productfeed\Model\Templates');
                $data = $this->getRequest()->getPostValue();
                if (isset($data)) {
                    $inputFilter = new \Magento\Framework\Filter\FilterInput(
                        [],
                        [],
                        $data
                    );
                    $data = $inputFilter->getUnescaped();
                    $id = $this->getRequest()->getParam('id');
                    if ($id) {
                        $model->load($id);
                        if ($id != $model->getId()) {
                            throw new \Magento\Framework\Exception\LocalizedException(__('The wrong template is specified.'));
                        }
                    }
                //print_R($data);
                
                
                    if ($data['type']=="csv") {
                        if (isset($data['csv']['name'])) {
                            if (array_filter($data['csv']['name'])) {
                                $data['content'] = json_encode($data['csv']);
                            }
                        } else {
                            $data['content'] = '';
                        }
                    } elseif ($data['type']=="xml") {
                        /* Remove Extra space from the xml content*/
                        $xml_content = null;
                        foreach ($data['xml'] as $key => $value) :
                            $xml_content[$key] = trim($value);
                        endforeach;
                        $data['content'] = json_encode($xml_content);
                    }
                
                    $model->setData($data);
                    $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                    $session->setPageData($model->getData());
                    $model->save();
                    $this->messageManager->addSuccess(__('You saved the template.'));
                    $session->setPageData(false);
                    if ($this->getRequest()->getParam('back')) {
                        $this->_redirect('magebees_productfeed/*/edit', ['id' => $model->getId()]);
                        return;
                    }
                 
                    $this->_redirect('magebees_productfeed/*/');
                    return;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('magebees_productfeed/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('magebees_productfeed/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the template data. Please review the error log.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('magebees_productfeed/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('magebees_productfeed/*/');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::templates');
    }
}
