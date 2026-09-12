<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Block\Post\View;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Blog post related category posts block
 */
class CategoryPosts extends \Magefan\Blog\Block\Post\PostList\AbstractList
{
    /**
     * Prepare posts collection
     *
     * @return void
     */
    protected function _preparePostCollection()
    {
        parent::_preparePostCollection();
        if ($category = $this->getCategory()) {
            $this->_postCollection->addCategoryFilter($category->getId());
        }
        $this->_postCollection->addFieldToFilter('post_id', ['neq' => $this->getPost()->getId()]);
    }

    /**
     * Retrieve page size
     * @return boolean
     */
    public function getPageSize()
    {
        $pageSize = (int) $this->_scopeConfig->getValue(
            'mfblog/post_view/category_posts/number_of_posts',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $pageSize;
    }

    /**
     * Retrieve true if Display Related Posts enabled
     * @return boolean
     */
    public function displayPosts()
    {
        return (bool) $this->_scopeConfig->getValue(
            'mfblog/post_view/category_posts/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) && $this->getCategory() && $this->getPost();
    }

    /**
     * Retrieve posts instance
     *
     * @return \Magefan\Blog\Model\Post
     */
    public function getPost()
    {
        if (!$this->hasData('post')) {
            $this->setData(
                'post',
                $this->_coreRegistry->registry('current_blog_post')
            );
        }
        return $this->getData('post');
    }

    /**
     * Retrieve categorys instance
     *
     * @return \Magefan\Blog\Model\Category
     */
    public function getCategory()
    {
        if (!$this->hasData('category')) {

            $category = $this->_coreRegistry->registry('current_blog_category');
            if (!$category) {

                $level = -1;
                foreach ($this->getPost()->getParentCategories() as $parentCategory) {
                    if ($parentCategory->getLevel() > $level) {
                        $level = $parentCategory->getLevel();
                        $category = $parentCategory;
                    }
                }
            }

            $this->setData('category', $category);
        }
        return $this->getData('category');
    }

    /**
     * Override this method in descendants to produce html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->displayPosts()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Retrieve identities
     *
     * @return array
     */
    public function getIdentities()
    {
        if (!$this->displayPosts()) {
            return [];
        }

        return parent::getIdentities();
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        $templateName = (string)$this->_scopeConfig->getValue(
            'mfblog/post_view/category_posts/template',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($template = $this->templatePool->getTemplate('blog_post_view_latest_from_category', $templateName)) {
            return $template;
        }
        return parent::getTemplate();
    }
}
