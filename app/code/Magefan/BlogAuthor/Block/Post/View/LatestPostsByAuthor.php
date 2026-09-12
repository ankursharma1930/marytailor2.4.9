<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Block\Post\View;

/**
 * Blog latest posts by author block
 */
class LatestPostsByAuthor extends \Magefan\Blog\Block\Post\PostList\AbstractList
{
    /**
     * Prepare posts collection
     *
     * @return void
     */
    protected function _preparePostCollection()
    {
        parent::_preparePostCollection();
        $this->_postCollection
            ->addAuthorFilter($this->getAuthor())
            ->setPageSize(
                $this->getAllowedPostCount() ?: 5
            );
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        if (null === $this->getData('author')) {
            $author = false;
            $post = $this->_coreRegistry->registry('current_blog_post');
            if ($post && $post->getAuthor() && $post->getAuthor()->getIsActive()) {
                $author = $post->getAuthor();
            }

            $this->setData('author', $author);
        }
        return $this->getData('author');
    }

    /**
     * Retrieve true if blog related posts are enabled
     *
     * @return bool
     */
    public function isRelatedPostsByAuthorEnabled()
    {
        return (bool)$this->_scopeConfig->getValue(
            \Magefan\BlogAuthor\Model\Config::XML_LATEST_POSTS_BY_AUTHOR_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve true if blog related posts are enabled
     *
     * @return bool
     */
    public function getAllowedPostCount()
    {
        return (int) $this->_scopeConfig->getValue(
            \Magefan\BlogAuthor\Model\Config::XML_LATEST_POSTS_BY_AUTHOR_NUMBER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        $templateName = (string)$this->_scopeConfig->getValue(
            'mfblog/post_view/latest_posts_by_author/template',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($template = $this->templatePool->getTemplate('blog_post_latest_posts_by_author', $templateName)) {
            return $template;
        }
        return parent::getTemplate();
    }
}
