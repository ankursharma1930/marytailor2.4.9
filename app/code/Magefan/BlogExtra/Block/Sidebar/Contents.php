<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Block\Sidebar;

/**
 * Blog sidebar post contents
 */
class Contents extends \Magefan\BlogExtra\Block\Post\View\Contents
{
    use \Magefan\Blog\Block\Sidebar\Widget;

    /**
     * @var string
     */
    protected $_widgetKey = 'contents';
}
