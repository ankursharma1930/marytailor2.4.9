<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Block\Adminhtml\Mapping\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Constructor
     *
     * @return void
     */
    protected $_template = 'mapping_form.phtml';
    protected function _construct()
    {
        parent::_construct();
        $this->setId('magebees_templates_form');
        $this->setTitle(__('Mapping Information'));
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        
        $model = $this->_coreRegistry->registry('current_magebees_productfeed_mapping');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('magebees_productfeed/mapping/save'),
                    'method' => 'post',
                ],
            ]
        );
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Mapping Information')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField(
            'name',
            'text',
            ['name' => 'name', 'label' => __('Name'), 'title' => __('Mapping Name'), 'required' => true]
        );
        
         $fieldset = $form->addFieldset('conditions_fieldset', ['legend' => __('Category Mapping List')]);
    /* Code Start For Set Custom Div In the form*/
        $fieldset->addType(
            'mapping_content',
            '\Magebees\Productfeed\Block\Adminhtml\Mapping\Edit\Renderer\MappingContent'
        );
        $fieldset->addField(
            'content',
            'mapping_content',
            [
            'name'  => 'content',
           
            ]
        );
    /* Code End For Set Custom Div In the form*/
    /* Code For Add Phtml Page in which js code added */
        $this->setChild(
            'content',
            $this->getLayout()->createBlock(
                'Magebees\Productfeed\Block\Adminhtml\Mapping\Edit\Tab\Content',
                'mapping.content'
            )
        );
    /* Code For Add Phtml Page in which js code compelted*/
        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
