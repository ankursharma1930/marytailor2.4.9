<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml\Import;

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
        $this->_controller = 'adminhtml_import';
        $this->_blockGroup = 'Magebees_Productfeed';
        parent::_construct();
        $this->buttonList->update('save', 'label', __('Import Templates'));
        $this->buttonList->update('back', 'onclick', "setLocation('" . $this->getUrl('magebees_productfeed/templates/') . "')");
    }
}
