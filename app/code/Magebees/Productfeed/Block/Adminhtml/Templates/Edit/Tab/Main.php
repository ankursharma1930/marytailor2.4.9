<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

// @codingStandardsIgnoreFile

namespace Magebees\Productfeed\Block\Adminhtml\Templates\Edit\Tab;


use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{

    /**
     * {@inheritdoc}
     */
	
	protected $_systemStore;
	protected $_yesno;
	 public function __construct(
		\Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
		\Magento\Config\Model\Config\Source\Yesno $yesno,
		\Magebees\Productfeed\Model\Templates $templates,
        array $data = array()
    ) {
        $this->_systemStore = $systemStore;
		
		$this->_yesno = $yesno;
		$this->templates = $templates;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    public function getTabLabel()
    {
        return __('Templates Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Templates Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_magebees_productfeed_templates');
		$isElementDisabled = false;
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Templates General Information')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField(
            'name',
            'text',
			['name' => 'name', 
			'label' => __('Name'),
			'title' => __('Name'),
			'required' => true, 
			]
        );
		$fieldset->addField(
            'type',
            'select',
			['name' => 'type', 
			'label' => __('Type'), 
			'title' => __('Type'), 
			'onchange' => 'content_type(this.value);',
			'required' => true, 
			'values'=> $this->templates->getTemplateType()]
        );
		
		
		
		
		
		
        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
