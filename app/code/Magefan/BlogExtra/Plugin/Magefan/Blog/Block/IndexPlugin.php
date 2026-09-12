<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Block;

use Magefan\Blog\Block\Index as Subject;
use Magefan\Blog\Model\Config\Source\PostsSortBy;
use Magefan\BlogExtra\Plugin\Magefan\Blog\Model\Config\Source\PostsSortByPlugin;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Store\Model\ScopeInterface;

class IndexPlugin
{
    const POSTS_SORT_FIELD_BY_END_TIME = 'end_time';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Subject $subject
     * @param string $result
     * @return string
     */
    public function afterGetCollectionOrderField(Subject $subject, string $result): string
    {
        $postsSortBy = $this->scopeConfig->getValue(
            \Magefan\Blog\Model\Config::XML_PATH_HOMEPAGE_POSTS_SORT_BY,
            ScopeInterface::SCOPE_STORE
        );

        switch (true) {
            case ($postsSortBy == PostsSortByPlugin::END_DATE_ASC ||
                $postsSortBy == PostsSortByPlugin::END_DATE_DESC):
                return self::POSTS_SORT_FIELD_BY_END_TIME;
            default:
                return $result;
        }
    }

    /**
     * @param Subject $subject
     * @param string $result
     * @return string
     */
    public function afterGetCollectionOrderDirection(Subject $subject, string $result): string
    {
        $postsSortBy = $this->scopeConfig->getValue(
            \Magefan\Blog\Model\Config::XML_PATH_HOMEPAGE_POSTS_SORT_BY,
            ScopeInterface::SCOPE_STORE
        );

        if (PostsSortBy::TITLE == $postsSortBy || PostsSortByPlugin::END_DATE_ASC == $postsSortBy) {
            return SortOrder::SORT_ASC;
        }

        return SortOrder::SORT_DESC;
    }
}
