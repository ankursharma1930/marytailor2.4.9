<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\BlogAuthor\Block\Author;

use Magento\Store\Model\ScopeInterface;

/**
 * Abstract author block
 */
abstract class AbstractAuthor extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magefan\Blog\Model\Config
     */
    protected $config;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magefan\Blog\Model\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magefan\Blog\Model\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $coreRegistry;
        $this->config = $config ;
    }

    /**
     * Retrieve author instance
     *
     * @return \Magefan\Blog\Api\AuthorInterface
     */
    public function getAuthor()
    {
        if (!$this->hasData('author')) {
            $this->setData(
                'author',
                $this->coreRegistry->registry('current_blog_author')
            );
        }
        return $this->getData('author');
    }
}
