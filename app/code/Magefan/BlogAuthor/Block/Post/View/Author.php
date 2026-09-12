<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Block\Post\View;

use Magefan\Blog\Block\Post\AbstractPost;

/**
 * Class Author
 * @package Magefan\BlogAuthor\Block\Post\View
 */
class Author extends AbstractPost
{
    /**
     * @return false|\Magefan\Blog\Api\AuthorInterface
     */
    public function getAuthor()
    {
        $author = $this->getPost()->getAuthor();
        if ($author && $author->getIsActive()) {
            return $author;
        }

        return false;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $canDisplay = $this->_scopeConfig->getValue(
            \Magefan\BlogAuthor\Model\Config::XML_AUTHOR_BLOCK_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) && $this->_scopeConfig->getValue(
            'mfblog/author/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($canDisplay) {
            return parent::_toHtml();
        }
        return '';
    }
}
