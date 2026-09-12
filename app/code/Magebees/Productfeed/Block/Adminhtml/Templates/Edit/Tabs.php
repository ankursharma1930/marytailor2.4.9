<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml\Templates\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('magebees_productfeed_templates_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Templates'));
    }
    protected function _beforeToHtml()
    {
    
        $this->addTab('main_section', [
                'label'         => __('General Information'),
                'title'         => __('General Information'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Templates\Edit\Tab\Main')->toHtml(),
            ]);
        
      
            
            $this->addTab('template_csv_section', [
                'label'         => __('Content'),
                'title'         => __('Content'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Templates\Edit\Tab\Content')->toHtml(),
            ]);
            $this->addTab('template_xml_section', [
                'label'         => __('Content'),
                'title'         => __('Content'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Templates\Edit\Tab\Xmlcontent')->toHtml(),
            ]);
        //}
     
            return parent::_beforeToHtml();
    }
}
