<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Block\Adminhtml;

/**
 * Admin blog author
 */
class Author extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml';
        $this->_blockGroup = 'Magefan_Blog';
        $this->_headerText = __('Author');
        $this->_addButtonLabel = __('Add New Author');
        parent::_construct();
        if (!$this->_authorization->isAllowed("Magefan_Blog::author_save")) {
            $this->removeButton('add');
        }
    }

//    /**
//     * @return $this
//     */
//    protected function _prepareLayout()
//    {
//        if ($this->_authorization->isAllowed("Magefan_Blog::import")) {
//            $onClick = "setLocation('" . $this->getUrl('*/import') . "')";
//            $this->getToolbar()->addChild(
//                'options_button',
//                \Magento\Backend\Block\Widget\Button::class,
//                ['label' => __('Import Authors'), 'onclick' => $onClick]
//            );
//        }
//        return parent::_prepareLayout();
//    }
}
