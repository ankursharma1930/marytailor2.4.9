<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml;

class Dynamicattributes extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_dynamicattributes';
        $this->_blockGroup = 'Magebees_Productfeed';
        $this->_headerText = __('Dynamic Attributes');
        $this->_addButtonLabel = __('Add New Dynamic Attributes');
        parent::_construct();
    }
}
