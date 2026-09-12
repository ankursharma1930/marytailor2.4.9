<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit;

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
        $this->setId('magebees_productfeed_feed_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Feed'));
    }
    protected function _beforeToHtml()
    {
    
        $this->addTab('main_section', [
                'label'         => __('General Information'),
                'title'         => __('General Information'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab\Main')->toHtml(),
            ]);
        
      
            $this->addTab('feed_csv_section', [
                'label'         => __('Content'),
                'title'         => __('Content'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab\Content')->toHtml(),
            ]);
            $this->addTab('feed_xml_section', [
                'label'         => __('Content'),
                'title'         => __('Content'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab\Xmlcontent')->toHtml(),
            ]);
            $this->addTab('conditions_section', [
                'label'         => __('Conditions'),
                'title'         => __('Conditions'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab\Conditions')->toHtml(), 
            ]);
            $this->addTab('categories_section', [
                'label'         => __('Category'),
                'title'         => __('Category'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab\Categories')->toHtml(),
            ]);
            $this->addTab('format_section', [
                'label'         => __('Format'),
                'title'         => __('Format'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab\Format')->toHtml(),
            ]);
            $this->addTab('schedule_section', [
                'label'         => __('Schedule'),
                'title'         => __('Schedule'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab\Schedule')->toHtml(),
            ]);
		$this->addTab('ftp_section', [
                'label'         => __('Ftp Settings'),
                'title'         => __('Ftp Settings'),
                'content'   => $this->getLayout()->createBlock('Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab\Ftpsetting')->toHtml(),
            ]);
		
        //}
     
            return parent::_beforeToHtml();
    }
}
