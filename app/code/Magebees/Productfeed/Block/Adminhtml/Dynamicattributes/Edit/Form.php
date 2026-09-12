<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Block\Adminhtml\Dynamicattributes\Edit;

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
        $this->setTitle(__('Dynamicattributes Information'));
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        
        $model = $this->_coreRegistry->registry('current_magebees_productfeed_dynamicattributes');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('magebees_productfeed/dynamicattributes/save'),
                    'method' => 'post',
                ],
            ]
        );
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __(' General Information ')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField(
            'name',
            'text',
            ['name' => 'name', 'label' => __('Attribute Name'), 'title' => __('Attribute Name'), 'required' => true]
        );
        $fieldset->addField(
            'code',
            'text',
            ['name' => 'code', 'label' => __('Attribute Code'), 'title' => __('Attribute Code'), 'required' => true]
        );
         $fieldset = $form->addFieldset('conditions_fieldset', ['legend' => __('Conditions')]);
    /* Code Start For Set Custom Div In the form*/
    
        $fieldset->addType(
            'condition_content',
            '\Magebees\Productfeed\Block\Adminhtml\Dynamicattributes\Edit\Renderer\Conditions'
        );
        $fieldset->addField(
            'conditions',
            'condition_content',
            [
            'name'  => 'conditions',
            'label' => __(''),
            'title' => __('Conditions'),
           
            ]
        );
    
    /* Code End For Set Custom Div In the form*/
    /* Code For Add Phtml Page in which js code added */
        $this->setChild(
            'content',
            $this->getLayout()->createBlock(
                'Magebees\Productfeed\Block\Adminhtml\Dynamicattributes\Edit\Tab\Content',
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
