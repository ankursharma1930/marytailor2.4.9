<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

// @codingStandardsIgnoreFile
namespace Magebees\Productfeed\Block\Adminhtml\Mapping\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{

	protected $_template = 'mapping_full_content.phtml';
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
		\Magebees\Productfeed\Model\Mapping $mapping,
        array $data = array()
    ) {
        $this->_systemStore = $systemStore;
		
		$this->_yesno = $yesno;
		$this->mapping = $mapping;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    public function getTabLabel()
    {
        return __('Mapping Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Mapping Information');
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
        $model = $this->_coreRegistry->registry('current_magebees_productfeed_mapping');
		$isElementDisabled = false;
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
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
		$fieldset->addType(
            'mycustomfield',
            '\Magebees\Productfeed\Block\Adminhtml\Mapping\Edit\Renderer\CustomRenderer'
        );
		
		
        $form->setValues($model->getData());
        $this->setForm($form);
        
return parent::_prepareForm();
    }
	
}
