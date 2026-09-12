<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml;

class Mapping extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_mapping';
        $this->_blockGroup = 'Magebees_Productfeed';
        $this->_headerText = __('Mapping');
        $this->_addButtonLabel = __('Add New Mapping');
        parent::_construct();
    }
}
