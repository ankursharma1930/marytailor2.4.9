<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
  
namespace Magebees\Productfeed\Block\Adminhtml\Mapping\Edit;

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
        $this->setId('magebees_productfeed_mapping_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Mapping'));
    }
    protected function _beforeToHtml()
    {
        $this->addTab('main_section', [
                'label'         => __('Mapping Information'),
                'title'         => __('Mapping Information'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Mapping\Edit\Tab\Main')->toHtml(),
            ]);
     
            
            
        return parent::_beforeToHtml();
    }
}
