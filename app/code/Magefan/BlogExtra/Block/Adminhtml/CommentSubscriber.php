<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Admin blog extra comment subscriber
 */
class CommentSubscriber extends Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_commentsubscriber';
        $this->_blockGroup = 'Magefan_BlogExtra';
        parent::_construct();
        $this->removeButton('add');
    }
}
