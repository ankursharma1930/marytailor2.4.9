<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Model\Facebook;

use \Magento\Framework\App\Request\Http;
use \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory as PostCollectionFactory;
use \Magefan\BlogPlus\Model\Config;
use \Magefan\Blog\Model\Url;

/**
 * Class RelatedPost
 */
class Publisher
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var CollectionFactory
     */
    protected $postsCollectionFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Url
     */
    protected $url;

    /**
     * Publisher constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param PostCollectionFactory $postsCollectionFactory
     * @param Config $config
     * @param Url|null $url
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        PostCollectionFactory $postsCollectionFactory,
        Config $config,
        Url $url = null
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->postsCollectionFactory = $postsCollectionFactory;
        $this->url = $url ?:\Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magefan\Blog\Model\Url::class);
    }

    /**
     * @param null $storeId
     * @return bool|\Facebook\Facebook
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getFbApi($storeId = null)
    {
        if (!$this->config->getFbAppId($storeId)
            || !$this->config->getFbSecretKey($storeId)
            || !$this->config->getFbPageId($storeId)) {
            return false;
        }
        return new \Facebook\Facebook([
            'app_id' => $this->config->getFbAppId($storeId),
            'app_secret' => $this->config->getFbSecretKey($storeId),
            'default_graph_version' => 'v3.0',
        ]);
    }

    /**
     * @param $post
     * @param null $storeId
     * @return bool
     */
    public function publish($post, $storeId = null)
    {
        $postData = $this->getPostData($post, $storeId);
        $fb = $this->getFbApi($storeId);
        try {
            $fb->post('/' . $this->config->getFbPageId($storeId) . '/feed', $postData, $this->config->getFbAccessToken());
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $this->logger->debug('Graph returned an error: ' . $e->getMessage());
            return false;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $this->logger->debug('Facebook SDK returned an error: ' . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @return $this
     */
    public function publishPosts()
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            if (!$storeId) {
                continue; //skip admin (zero) store view
            }
            if ($this->config->isFbPublishEnabled($storeId)) {
                $posts = $this->getPublishPostCollection($storeId);
                foreach ($posts as $post) {
                    $result = $this->publish($post, $storeId);

                    if ($result) {
                        $post->setFbPublished(1);
                        $post->save();
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param null $storeId
     * @return \Magefan\Blog\Model\ResourceModel\Post\Collection
     */
    public function getPublishPostCollection($storeId = null)
    {
        $postsCollection = $this->postsCollectionFactory->create()
            ->addActiveFilter()
            ->addFieldToFilter('fb_published', ['null' => true])
            ->addFieldToFilter('fb_auto_publish', true)
            ->addFieldToFilter('publish_time', ['gteq' => time() - 86400]);

        if ($storeId) {
            $postsCollection->addStoreFilter($storeId);
        }

        $categories = $this->config->getFbAutopublishCategories($storeId);
        if (!in_array(0, $categories)) {
            $postsCollection->addCategoryFilter($categories);
        }
        return $postsCollection;
    }

    /**
     * @param $post
     * @param null $storeId
     * @return array
     */
    public function getPostData($post, $storeId = null)
    {
        $blogUrl = $this->url;
        $blogUrl->startStoreEmulation($this->storeManager->getStore($storeId));
        $url = $blogUrl->getUrl($post, $blogUrl::CONTROLLER_POST);
        $blogUrl->stopStoreEmulation();

        $postData = [];
        $postData['link'] = $url;
        $postData['message'] = $post->getOgDescription();
        return $postData;
    }

    /**
     * @return \Magefan\BlogPlus\Model\Config
     */
    public function getConfig()
    {
        return $this->config;
    }
}
