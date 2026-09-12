<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml\Import\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Form extends Generic implements TabInterface
{
    
    /**
     * {@inheritdoc}
     */
    
    protected $_systemStore;
    protected $import;
    protected $_objectManager;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \Magebees\Productfeed\Model\Import $import,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        
        
        $this->import = $import;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('magebees_templates_import_form');
        $this->setTitle(__('Import Template Information'));
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('magebees_productfeed/import/importtemplate'),
                    'method' => 'post',
                ],
            ]
        );
        $isElementDisabled = false;
        
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Import Templates Information')]);
        
        $fieldset->addField(
            'import_templates',
            'multiselect',
            ['name' => 'import_templates',
            'label' => __('Templates'),
            'title' => __('Templates'),
            'required' => true,
            'values'=> $this->import->getImportedTemplateList(),
            ]
        );
        
        $this->setForm($form);
        $form->setUseContainer(true);
       
        
        return parent::_prepareForm();
    }
    public function getTabLabel()
    {
        return __('Import Templates Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Import Templates Information');
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
}
