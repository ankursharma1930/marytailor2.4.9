<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\YouTubeWidget\Block\Adminhtml\System\Config\Form;

/**
 * Admin blog configurations information block
 */
class Info extends \Magefan\Community\Block\Adminhtml\System\Config\Form\Info
{
    /**
     * Return extension url
     * @return string
     */
    protected function getModuleUrl()
    {
        return 'https://magefan.com/magento2-youtube-extension?utm_source=m2admin_youtube_config&utm_medium=link&utm_campaign=regular';
    }

    /**
     * Return extension title
     * @return string
     */
    protected function getModuleTitle()
    {
        return 'YouTube Widget Extension';
    }
}
