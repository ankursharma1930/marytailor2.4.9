<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml\Mapping;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_mapping';
        $this->_blockGroup = 'Magebees_Productfeed';

        parent::_construct();

        $this->buttonList->add(
            'save_and_continue_edit',
            [
                'class' => 'save',
                'label' => __('Save and Continue Edit'),
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ],
            10
        );
        
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('block_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'label_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'label_content');
                }
            }
		var url_mapping = '".$this->getUrl('*/*/CategoryMapping')."';
			
		";
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $templates = $this->_coreRegistry->registry('current_magebees_productfeed_mapping');
        if ($item->getId()) {
            return __("Edit Mapping '%1'", $this->escapeHtml($templates->getName()));
        } else {
            return __('New Mapping');
        }
    }
}
