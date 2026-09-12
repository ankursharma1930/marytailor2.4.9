<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Helper;

use Magento\Framework\UrlInterface;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DIR_NAME = 'recipe';

    const PICTURE_STUB = 'recipe-image-coming-soon.png';

    protected $attributes = [];

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * @var \Magento\Framework\Image\Factory
     */
    protected $_imageFactory;

    /**
     * @var \Magento\Framework\View\ConfigInterface
     */
    protected $_viewConfig;

    /**
     * @var \Magento\Framework\Config\View
     */
    protected $_configView;

    /**
     * Image constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Image\Factory $imageFactory
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Framework\View\ConfigInterface $viewConfig
    ) {
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->_storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->_imageFactory = $imageFactory;
        $this->_viewConfig = $viewConfig;

        parent::__construct($context);
    }

    /**
     * @param $imageUrl
     * @param $imageId
     * @param int $quality
     * @param null $background
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getResizedImage($imageUrl, $imageId, $quality = 100, $background = null)
    {
        if (!trim($imageUrl)) {
            return false;
        }

        $imageUrl = $this->checkIsImageRemote($imageUrl);

        $imagePath = $this->getRelatedPath($imageUrl);
        if (!$imagePath) {
            return false;
        }

        $whAttributes = $this->getImageAttributes($imageId);
        $width = $whAttributes['width'] ? (int)$whAttributes['width'] : null;
        $height = $whAttributes['height'] ? (int)$whAttributes['height'] : null;
        $absoluteResizeDirPath = $this->getAbsoluteResizeDir($width, $height);
        $imageResizeAbsolutePath = $absoluteResizeDirPath . $imagePath;

        if (!$this->_mediaDirectory->isExist($imageResizeAbsolutePath)) {
            try {
                $imageObj = $this->_imageFactory->create($this->getBaseMediaDir() . $imagePath);
                $imageObj->constrainOnly(true);
                $imageObj->keepAspectRatio(true);
                $imageObj->keepTransparency(true);
                if (is_string($background)) {
                    $color = explode(',', $background);
                    $imageObj->backgroundColor($color);
                } elseif (is_array($background)) {
                    $imageObj->backgroundColor($background);
                } else {
                    $imageObj->backgroundColor([255, 255, 255]);
                }
                $imageObj->keepFrame(true);
                $imageObj->quality($quality);
                $imageObj->resize($width, $height);
                $imageObj->save($absoluteResizeDirPath . $imagePath);
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }

        return $this->getWebUrl($imagePath, $width, $height);
    }

    /**
     * @param $imageId
     * @return array
     */
    protected function getImageAttributes($imageId)
    {
        return $this->attributes = array_merge(
            $this->getConfigView()
                ->getMediaAttributes(
                    'Mageside_Recipe',
                    \Magento\Catalog\Helper\Image::MEDIA_TYPE_CONFIG_NODE,
                    $imageId
                )
        );
    }

    /**
     * @return \Magento\Framework\Config\View
     */
    protected function getConfigView()
    {
        if (!$this->_configView) {
            $this->_configView = $this->_viewConfig->getViewConfig();
        }
        return $this->_configView;
    }

    /**
     * @param $width
     * @param $height
     * @return string
     */
    protected function getAbsoluteResizeDir($width, $height)
    {
        return $this->getBaseMediaDir() . $this->getResizeDir($width, $height);
    }

    /**
     * @param $width
     * @param $height
     * @return string
     */
    protected function getResizeDir($width, $height)
    {
        return self::DIR_NAME . DIRECTORY_SEPARATOR .
            'resized' . DIRECTORY_SEPARATOR . $width . 'x' . $height . DIRECTORY_SEPARATOR;
    }

    /**
     * @param $imagePath
     * @param $width
     * @param $height
     * @return string
     */
    protected function getWebUrl($imagePath, $width, $height)
    {
        return $this->getMediaUrl() . $this->getResizeDir($width, $height) . $imagePath;
    }

    /**
     * @param $url
     * @return mixed|string
     */
    protected function getRelatedPath($url)
    {
        $url = strstr($url, self::DIR_NAME);
        $url = preg_replace('/\s+/', '', $url);
        $url = str_replace("/", DIRECTORY_SEPARATOR, $url);

        return $url;
    }

    /**
     * @return mixed
     */
    protected function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * @return string
     */
    protected function getBaseMediaDir()
    {
        return $this->_filesystem
            ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->getAbsolutePath();
    }

    /**
     * @param $imageUrl
     * @return string
     */
    protected function checkIsImageRemote($imageUrl)
    {
        $imageUrlInfo = parse_url($imageUrl);
        $baseUrlInfo = parse_url($this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB));

        if (isset($imageUrlInfo['host']) && $baseUrlInfo['host'] != $imageUrlInfo['host']) {
            $localImagePath = self::DIR_NAME . DIRECTORY_SEPARATOR . 'remote' . $imageUrlInfo['path'];
            $fullImagePath = $this->getBaseMediaDir() . str_replace('/', DIRECTORY_SEPARATOR, $localImagePath);
            $fullImageUrl = $this->getMediaUrl() . $localImagePath;
            $finfo = pathinfo($fullImagePath);
            if (!$this->_mediaDirectory->isExist($finfo['dirname'])) {
                $this->_mediaDirectory->create($finfo['dirname']);
            }
            $download_image = true;
            if ($this->_mediaDirectory->isExist($fullImagePath)) {
                $download_image = false;
            }
            if ($download_image == true) {
                $img = file_get_contents($imageUrl);
                file_put_contents($fullImagePath, $img);
            }
            $imageUrl = $fullImageUrl;

            return $imageUrl;
        } else {
            return $imageUrl;
        }
    }

    /**
     * @param $image
     * @return string|null
     */
    public function getMediaLink($image)
    {
        if ($image) {
            return $this->getMediaUrl() . self::DIR_NAME . '/' . $image;
        }

        return null;
    }
}
