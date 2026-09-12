<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Dynamicattributes;

class Save extends \Magebees\Productfeed\Controller\Adminhtml\Dynamicattributes
{
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                $available_attributes = $this->_templates->getAttributeList();
                
                $model = $this->_objectManager->create('Magebees\Productfeed\Model\Dynamicattributes');
                $data = $this->getRequest()->getPostValue();
                
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
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong condition is specified.'));
                    }
                }
                
                /* Convert condition into json*/
                if (isset($data['condition']['attr'])) {
                    unset($data['condition']['attr']);
                    $conditions = $data['condition'];
                }
                if (isset($data['condition'])) {
                    $data['codeconditions'] =  json_encode($conditions);
                }
                $attribute_code = str_replace(' ', '_', strtolower($data['code']));

                if (!$id) {
                    $available = 0;
                    foreach ($available_attributes as $key => $attributes) :
                        if (array_key_exists($attribute_code, $attributes)) {
                            $available = 1;
                            break;
                        }
                    endforeach;
                    if ($available==1) {
                        $this->messageManager->addNotice(__('Attribute code already exists.'));
                        $this->_redirect('magebees_productfeed/*/');
                        return;
                    }
                }
                
                $data['code'] = $attribute_code;
                $model->setData($data);
                $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                $session->setPageData($model->getData());
                $model->save();
                $this->messageManager->addSuccess(__('You saved the conditions.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('magebees_productfeed/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('magebees_productfeed/*/');
                return;
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
                    __('Something went wrong while saving the conditions data. Please review the error log.')
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
        return $this->_authorization->isAllowed('Magebees_Productfeed::dynamicattributes');
    }
}
