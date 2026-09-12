<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Model;

use Magefan\Blog\Api\ShortContentExtractorInterface;
use Magefan\Blog\Model\Url;

/**
 * Blog author model
 */
class Author extends \Magefan\Blog\Model\Author
{
    /**
     * Author Status
     */
    const STATUS_ENABLED = 1;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * @var ShortContentExtractorInterface
     */
    protected $shortContentExtractor;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'magefan_blog_author';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'blog_author';

    /**
     * Author constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Url $url
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Url $url,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $url, $storeManager, $scopeConfig, $resource, $resourceCollection, $data);
        $this->scopeConfig = $scopeConfig;
        $this->filterProvider = $filterProvider;
    }

    /**
     * Base media folder path
     */
    const BASE_MEDIA_PATH = 'magefan_blogauthor';


    /**
     * Initialize user model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magefan\BlogAuthor\Model\ResourceModel\Author::class);
        $this->_collectionName = \Magefan\BlogAuthor\Model\ResourceModel\Author\Collection::class;
    }

    /**
     * Retrieve if is visible on store
     * @param null|int $storeId
     * @return bool
     */
    public function isVisibleOnStore($storeId): bool
    {
        return $this->getIsActive()
            && (null === $storeId || array_intersect([0, $storeId], $this->getStoreIds()));
    }

    /**
     * Retrieve model title
     * @param  boolean $plural
     * @return string
     */
    public function getOwnTitle($plural = false)
    {
        return $plural ? 'Authors' : 'Author';
    }

    /**
     * @return mixed|null|string
     */
    public function getIdentifier()
    {
        return  $this->getData('identifier');
    }

    /**
     * Check if author identifier exist
     * return author id if author exists
     *
     * @param string $identifier
     * @return int
     */
    public function checkIdentifier($identifier)
    {
        return $this->_getResource()->checkIdentifier($identifier);
    }

    /**
     * Retrieve featured image url
     * @return string
     */
    public function getFeaturedImage()
    {
        if (!$this->hasData('featured_image')) {
            if ($file = $this->getData('featured_img')) {
                $image = $this->_url->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('featured_image', $image);
        }

        return $this->getData('featured_image');
    }

    /**
     * Retrieve filtered content
     *
     * @return string
     */
    public function getFilteredContent()
    {
        $key = 'filtered_content';
        if (!$this->hasData($key)) {
            $content = $this->filterProvider->getPageFilter()->filter(
                (string) $this->getContent() ?: ''
            );
            $this->setData($key, $content);
        }
        return $this->getData($key);
    }

    /**
     * Retrieve short filtered content
     * @param  mixed $len
     * @param  mixed $endCharacters
     * @return string
     */
    public function getShortFilteredContent($len = null, $endCharacters = null)
    {
        $key = 'short_filtered_content' . $len;
        if (!$this->hasData($key)) {
            if ($this->getShortContent()) {
                $content = (string)$this->getShortContent() ?: '';
            } else {
                //$content = $this->getFilteredContent();
                $content = (string)$this->getContent() ?: '';
            }

            $content = $this->getShortContentExtractor()->execute($content, $len, $endCharacters);

            $this->setData($key, $content);
        }
        return $this->getData($key);
    }

    /**
     * Retrieve true if author is active
     * @return boolean
     */
    public function isActive()
    {
        return ($this->getIsActive() == self::STATUS_ENABLED);
    }

    /**
     * @return ShortContentExtractorInterface
     */
    public function getShortContentExtractor()
    {
        if (null === $this->shortContentExtractor) {
            $this->shortContentExtractor = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(ShortContentExtractorInterface::class);
        }

        return $this->shortContentExtractor;
    }
}
