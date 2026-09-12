<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Model;

/**
 * Class SystemConfig
 */
class Config extends \Magefan\BlogPlus\Model\Config
{
    const XML_PATH_TO_BLOG_SEARCH_ENABLE = 'mfblog/blog_search/enable_blog_search';
    const XML_PATH_TO_BLOG_PROGRESS_BAR_PATH = 'mfblog/post_view/reading_progress_bar/';

    /**
     * @param null $storeId
     * @return bool
     */
    public function isBlogSearchEnabled($storeId = null)
    {
        return (bool)$this->getConfig(
            self::XML_PATH_TO_BLOG_SEARCH_ENABLE,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getReadingProgressBarConfigData($path, $storeId = null)
    {
        return (string)$this->getConfig(
            self::XML_PATH_TO_BLOG_PROGRESS_BAR_PATH . $path,
            $storeId
        );
    }
}
