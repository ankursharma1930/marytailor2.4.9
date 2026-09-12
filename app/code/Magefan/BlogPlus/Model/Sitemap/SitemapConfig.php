<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogPlus\Model\Sitemap;

use Magefan\Blog\Api\SitemapConfigInterface;

/**
 * Class SitemapConfig
 */
class SitemapConfig extends \Magefan\Blog\Model\Config implements SitemapConfigInterface
{
    /**
     * @param $page
     * @param $storeId
     * @return bool
     */
    public function isEnabledSitemap($page, $storeId = null):bool
    {
        if ($page == 'author') {
            return $this->isEnabled($storeId) && $this->getValue($page, 'enabled', $storeId) && $this->isAuthorPageEnabled($page, $storeId) && $this->isRobotsAllowed($page, $storeId);
        } elseif ($page == 'tag') {
            return $this->isEnabled($storeId) && $this->getValue($page, 'enabled', $storeId) && $this->isRobotsAllowed($page, $storeId);
        }
        return $this->isEnabled($storeId) && $this->getValue($page, 'enabled', $storeId);
    }

    /**
     * @param $page
     * @param $storeId
     * @return string
     */
    public function getFrequency($page, $storeId = null):string
    {
        return (string)$this->getValue($page, 'frequency', $storeId);
    }

    /**
     * @param $page
     * @param $storeId
     * @return float
     */
    public function getPriority($page, $storeId = null):float
    {
        return (float)$this->getValue($page, 'priority', $storeId);
    }

    /**
     * @param $page
     * @param $type
     * @param $storeId
     * @return mixed
     */
    public function getValue($page, $type, $storeId = null)
    {
        return $this->getConfig('mfblog/sitemap/' . $page . '/' . $type, $storeId);
    }

    /**
     * @param $page
     * @param $storeId
     * @return mixed
     */
    public function isAuthorPageEnabled($page, $storeId = null)
    {
        return $this->getConfig('mfblog/' . $page . '/page_enabled', $storeId);
    }

    /**
     * @param $page
     * @param $storeId
     * @return mixed
     */
    public function isRobotsAllowed($page, $storeId = null)
    {
        $allowedRobots = $this->getConfig('mfblog/' . $page . '/robots', $storeId);
        return $allowedRobots == 'INDEX,FOLLOW' || $allowedRobots == 'INDEX,NOFOLLOW';
    }
}
