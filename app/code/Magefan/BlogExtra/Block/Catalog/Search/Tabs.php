<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Block\Catalog\Search;

use Magento\Framework\View\Element\Template;
use Magefan\BlogExtra\Model\Config;

/**
 * Class Search Tabs
 */
class Tabs extends \Magento\Framework\View\Element\Template
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var bool
     */
    private $_hasProducts;

    /**
     * Tabs constructor.
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPostsCount()
    {
        $postListBlock = $this->getLayout()->getBlock('blog.post.list');
        if (!$postListBlock) {
            $postListBlock = $this->getLayout()->createBlock(
                \Magefan\BlogExtra\Block\Catalog\Search\ListPost::class
            );
        }

        $collection = $postListBlock->getPostCollection();
        return $collection->getSize();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function hasProducts()
    {
        if (null === $this->_hasProducts) {
            if ($this->getRequest()->getParam('noproducts')) {
                $this->_hasProducts = false;
            } else {
                $searchResultBlock = $this->getLayout()->getBlock('search.result');
                $this->_hasProducts = true;
                if ($searchResultBlock) {
                    if ($listBlock = $searchResultBlock->getListBlock()) {
                        $collection = clone $listBlock->getLoadedProductCollection();
                        $this->_hasProducts = (bool)$collection->getSize();
                    }
                }
            }
        }

        return $this->_hasProducts;
    }

    /**
     * @return string
     */
    public function getCatalogProductsTabUrl()
    {
        return $this->getUrl('catalogsearch/result/index', ['q' => $this->getRequest()->getParam('q')]);
    }

    /**
     * @return string
     */
    public function getBlogPostsTabUrl()
    {
        //return $this->getUrl('catalogsearch/result/index', ['q' => $this->getRequest()->getParam('q'), 'type' => 'blog']);
        /* Use this custom URL creator becose of different custom modules on the search page with custom search URLs */
        $cUrl = $this->_storeManager->getStore()->getCurrentUrl(false);
        $urlInfo = parse_url($cUrl);

        if (!empty($urlInfo['query'])) {
            $query = [];
            parse_str($urlInfo['query'], $query);
            if (isset($query['___store'])) {
                unset($query['___store']);
            }
        } else {
            $query = [];
        }

        $query['type'] = 'blog';
        if (!$this->hasProducts()) {
            $query['noproducts'] = 1;
        }

        return $urlInfo['scheme'] . '://' . $urlInfo['host'] . $urlInfo['path'] . '?' . http_build_query($query);
    }

    /**
     * @return string
     */
    public function getActiveTabName()
    {
        if ('blog' == $this->getRequest()->getParam('type')) {
            return 'blog';
        } else {
            return 'product';
        }
    }

    /**
     * Override this method in descendants to produce html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->config->isEnabled() && $this->config->isBlogSearchEnabled()) {
            return parent::_toHtml();
        }
        return '';
    }
}
