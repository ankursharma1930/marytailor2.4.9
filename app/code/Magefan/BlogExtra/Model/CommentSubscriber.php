<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Url\EncoderInterface;

/**
 * Blog extra comment subscriber model
 */
class CommentSubscriber extends AbstractModel implements IdentityInterface
{
    /**
     * Comment subscriber statuses
     *
     * @const int
     */
    const NEED_TO_SEND = 1;

    /**
     * @const int
     */
    const NOT_NEED_TO_SEND = 0;

    /**
     * @const int
     */
    const SENT = 1;

    /**
     * @const int
     */
    const NOT_SENT = 0;

    /**
     * @const int
     */
    const SUBSCRIBED = 1;

    /**
     * @const int
     */
    const UNSUBSCRIBED = 0;

    /**
     * blog extra cache comment subscriber
     */
    const CACHE_TAG = 'magefan_blog_comment_subscriber';

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlInterface
     * @param EncryptorInterface $encryptor
     * @param EncoderInterface $encoder
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface,
        EncryptorInterface $encryptor,
        EncoderInterface $encoder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->urlInterface = $urlInterface;
        $this->encryptor = $encryptor;
        $this->encoder = $encoder;
        return parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magefan\BlogExtra\Model\ResourceModel\CommentSubscriber::class);
    }

    /**
     * Retrieve identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Retrieve model title
     *
     * @param  boolean $plural
     * @return string
     */
    public function getOwnTitle($plural = false)
    {
        return $plural ? 'Comment Subscribers' : 'Comment Subscriber';
    }

    /**
     * Get unsubscribe link
     *
     * @param null $postId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _getUnsubscribeLink($postId = null)
    {
        $storeId = $this->getStoreId();

        if ($storeId) {
            $store = $this->storeManager->getStore($storeId);
        } else {
            $store = $this->storeManager->getDefaultStoreView();
        }

        $key = $this->encryptor->encrypt($postId . $this->getData('email'));
        $redirectUrl = $this->urlInterface->setScope($store)
            ->getUrl('blogextra/notification/unsubscribe', [
                'post_id' => $postId,
                'email' => $this->getData('email'),
                'key' => $this->encoder->encode($key)
            ]);

        return $redirectUrl;
    }

    /**
     * Get unsubscribe link from all posts
     *
     * @param null $postId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUnsubscribeAllLink()
    {
        return $this->_getUnsubscribeLink();
    }

    /**
     * Get unsubscribe link
     *
     * @param null $postId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUnsubscribeLink($postId = null)
    {
        $postId = $this->getData('post_id');
        return $this->_getUnsubscribeLink($postId);
    }
}
