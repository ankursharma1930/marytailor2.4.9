<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml\Templates;

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
        $this->_controller = 'adminhtml_templates';
        $this->_blockGroup = 'Magebees_Productfeed';

        parent::_construct();
        
        $templates = $this->_coreRegistry->registry('current_magebees_productfeed_templates');
        $export_template = $this->getUrl('magebees_productfeed/templates/exporttemplate', ['id' => $templates->getId()]);

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
        $this->addButton(
            'export',
            ['label' => __('Export'), 'onclick' => 'setLocation("'.$export_template.'")', 'class' => 'export-template'],
            -1
        );
        //$this->buttonList->update('save_and_continue_edit', 'onclick', 'submitForm()');
        $this->_formScripts[] = "
		 window.onload = contenttype;
           function contenttype(){
				var e = document.getElementById('item_type');
				var itemtype = e.options[e.selectedIndex].value;
				if(itemtype=='xml'){
				document.getElementById('magebees_productfeed_templates_edit_tabs_template_xml_section').style.display = 'block';
				document.getElementById('magebees_productfeed_templates_edit_tabs_template_csv_section').style.display = 'none';
				}else if(itemtype=='csv'){
					document.getElementById('magebees_productfeed_templates_edit_tabs_template_xml_section').style.display = 'none';
					document.getElementById('magebees_productfeed_templates_edit_tabs_template_csv_section').style.display = 'block';
				}
				content_type(itemtype);
			}
			function content_type(value){
				if(value!=''){
				if(value=='xml'){
				document.getElementById('magebees_productfeed_templates_edit_tabs_template_xml_section').style.display = 'block';
				document.getElementById('magebees_productfeed_templates_edit_tabs_template_csv_section').style.display = 'none';
				}else if(value=='csv'){
					document.getElementById('magebees_productfeed_templates_edit_tabs_template_xml_section').style.display = 'none';
					document.getElementById('magebees_productfeed_templates_edit_tabs_template_csv_section').style.display = 'block';
				}	
					
				}
			}
			
			";
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $templates = $this->_coreRegistry->registry('current_magebees_productfeed_templates');
        if ($item->getId()) {
            return __("Edit Template '%1'", $this->escapeHtml($templates->getName()));
        } else {
            return __('New Template');
        }
    }
    /*
	public function getValidationUrl()
    {
    
	}*/
}
