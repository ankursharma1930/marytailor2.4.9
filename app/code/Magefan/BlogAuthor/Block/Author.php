<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Block;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Magefan\BlogAuthor\Model\Author as AuthorModel;

/**
 * Class Author
 * @package Magefan\BlogAuthor\Block
 */
class Author extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registr
     */
    public $coreRegistry;

    /**
     * AuthorHome constructor.
     * @param \Magento\Framework\Registry $coreRegistry
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * @return false|\Magefan\Blog\Api\AuthorInterface
     */
    public function getAuthor()
    {
        $author = $this->_coreRegistry->registry('current_blog_author');
        if ($author->getIsActive()) {
            return $author;
        }

        return false;
    }

    /**
     * Retrieve 1 if author page is enabled
     * @return int
     */
    public function authorPageEnabled()
    {
        return (int) $this->_scopeConfig->getValue(
            'mfblog/author/page_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
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
