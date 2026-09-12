<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Model;

/**
 * Magefan Blog Config Model
 */
class Config extends \Magefan\Blog\Model\Config
{
    const XML_AUTO_RELATED_POSTS_ENABLED        = 'mfblog/post_view/related_posts/autorelated_enabled';
    const XML_AUTO_RELATED_POSTS_BLACK_WORDS    = 'mfblog/post_view/related_posts/autorelated_black_words';
    const XML_AUTO_RELATED_PRODUCTS_ENABLED     = 'mfblog/post_view/related_products/autorelated_enabled';
    const XML_AUTO_RELATED_PRODUCTS_BLACK_WORDS = 'mfblog/post_view/related_products/autorelated_black_words';
    
    /** Facebook autopublish configs  */
    const XML_PATH_TO_FB_PUBLISH_ENABLED = 'mfblog/publish_post_on_fb/enabled';
    const XML_PATH_TO_FB_PAGE_ID = 'mfblog/publish_post_on_fb/page_id';
    const XML_PATH_TO_FB_APP_ID = 'mfblog/publish_post_on_fb/app_id';
    const XML_PATH_TO_FB_SECRET_KEY = 'mfblog/publish_post_on_fb/app_secret';
    const XML_PATH_TO_FB_ACCESS_TOKEN = 'mfblog/publish_post_on_fb/access_token';
    const XML_PATH_TO_FB_AUTOPUBLISH_CATEGORIES = 'mfblog/publish_post_on_fb/autopublish_categories';

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var array|null
     */
    protected $autoRelatedBlackWords;

    /**
     * Retrieve true if blog auto related posts are enabled
     *
     * @return bool
     */
    public function isAutoRelatedPostsEnabled($storeId = null)
    {
        return (bool)$this->getConfig(
            self::XML_AUTO_RELATED_POSTS_ENABLED,
            $storeId
        ) && $this->isRelatedPostsEnabled($storeId);
    }

    /**
     * Retrieve true if blog auto related products are enabled
     *
     * @return bool
     */
    public function isAutoRelatedProductsEnabled($storeId = null)
    {
        return $this->getConfig(
            self::XML_AUTO_RELATED_PRODUCTS_ENABLED,
            $storeId
        ) && $this->isRelatedProductsEnabled($storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getAutoRelatedPostsBlackWords($storeId = null)
    {
        return $this->getAutoRelatedBlackWords(
            self::XML_AUTO_RELATED_POSTS_BLACK_WORDS,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return array|null
     */
    public function getAutoRelatedProductsBlackWords($storeId = null)
    {
        return $this->getAutoRelatedBlackWords(
            self::XML_AUTO_RELATED_PRODUCTS_BLACK_WORDS,
            $storeId
        );
    }

    /**
     * @param $configPath
     * @param $storeId
     * @return mixed
     */
    protected function getAutoRelatedBlackWords($configPath, $storeId)
    {
        if (!isset($this->autoRelatedBlackWords[$configPath])) {
            $blackWords = $this->getConfig($configPath, $storeId) ?: '';
            $blackWords = explode(',', $blackWords);
            foreach ($blackWords as $key => $value) {
                $value = trim($value);
                if ($value) {
                    $blackWords[$key] = $value;
                } else {
                    unset($blackWords[$key]);
                }
            }
            $this->autoRelatedBlackWords[$configPath] = $blackWords;
        }
        return $this->autoRelatedBlackWords[$configPath];
    }

    /**
     * @return mixed
     */
    public function isFbPublishEnabled($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_TO_FB_PUBLISH_ENABLED,
            $storeId
        );
    }

    /**
     * @return mixed
     */
    public function getFbPageId($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_TO_FB_PAGE_ID,
            $storeId
        );
    }

    /**
     * @return mixed
     */
    public function getFbAppId($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_TO_FB_APP_ID,
            $storeId
        );
    }

    /**
     * @param $secretKey
     * @return $this
     */
    public function getFbSecretKey($storeId = null)
    {
        $value = $this->getConfig(
            self::XML_PATH_TO_FB_SECRET_KEY,
            $storeId
        );

        return $value ? $this->getEncryptor()->decrypt($value) : $value;
    }

    /**
     * @return mixed
     */
    public function getFbAccessToken($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_TO_FB_ACCESS_TOKEN,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return array|mixed
     */
    public function getFbAutopublishCategories($storeId = null)
    {
        $ids = $this->getConfig(
            self::XML_PATH_TO_FB_AUTOPUBLISH_CATEGORIES,
            $storeId
        );

        $ids = explode(',', $ids);

        return $ids;
    }

    protected function getEncryptor()
    {
        if (null === $this->encryptor) {
            $this->encryptor = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Encryption\EncryptorInterface::class);
        }
        return $this->encryptor;
    }
}
