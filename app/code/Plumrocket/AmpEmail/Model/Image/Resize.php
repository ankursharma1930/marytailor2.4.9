<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;

class Resize implements \Plumrocket\AmpEmail\Api\ResizeImageInterface
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var \Magento\Framework\Image\AdapterFactory
     */
    private $imageFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Resizer constructor.
     *
     * @param \Magento\Framework\Filesystem              $filesystem
     * @param \Magento\Framework\Image\AdapterFactory    $imageFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface                   $logger
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->imageFactory = $imageFactory;
        $this->storeManager = $storeManager;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->logger = $logger;
    }

    /**
     * @param string $image
     * @param int    $containerWidth
     * @param int    $containerHeight
     * @param string $mediaFolder
     * @param string $additionalPath
     * @param bool   $needCrop
     * @return array|bool
     */
    public function execute(
        string $image,
        int $containerWidth,
        int $containerHeight,
        string $mediaFolder,
        string $additionalPath = '',
        bool $needCrop = true
    ) {
        $absolutePath = $this->mediaDirectory->getAbsolutePath($mediaFolder) . DIRECTORY_SEPARATOR . $image;

        if (! $image || ! $this->mediaDirectory->isExist($absolutePath)) {
            return false;
        }

        $mediaFolder = trim($mediaFolder, '/');
        $image = ltrim($image, '/');
        $path = "$mediaFolder/cache/$additionalPath/$containerWidth/$containerHeight/";
        $path = trim($path, '/') . '/';

        $imageResized = $this->mediaDirectory->getAbsolutePath($path) . $image;

        if (! $this->mediaDirectory->isFile($path . $image)) {
            /** @var \Magento\Framework\Image\Adapter\Gd2 $imageObject */
            $imageObject = $this->imageFactory->create();
            $imageObject->open($absolutePath);

            $originalWidth =  $imageObject->getOriginalWidth();
            $originalHeight = $imageObject->getOriginalHeight();

            $fillImageWidth = null;
            $fillImageHeight = null;
            $cropTop = null;
            $cropLeft = null;
            $cropRight = null;
            $cropBottom = null;

            if (! $containerWidth) {
                $containerWidth = round($containerHeight * ($originalWidth / $originalHeight));
            }

            if (! $containerHeight) {
                $containerHeight = round($containerWidth * ($originalHeight / $originalWidth));
            }

            if ($needCrop) {
                $sizes = $this->getFillImageSize(
                    $containerWidth,
                    $containerHeight,
                    $originalWidth,
                    $originalHeight
                );

                $fillImageWidth = $sizes['width'];
                $fillImageHeight = $sizes['height'];

                $crop = $this->getFillImageCrop($containerWidth, $containerHeight, $fillImageWidth, $fillImageHeight);

                $cropTop = $crop['top'];
                $cropLeft = $crop['left'];
                $cropRight = $crop['right'];
                $cropBottom = $crop['bottom'];
            }

            $imageObject->constrainOnly(true);
            $imageObject->keepTransparency(true);
            $imageObject->backgroundColor([255, 255, 255]);
            $imageObject->keepFrame(true);
            $imageObject->keepAspectRatio(true);
            $imageObject->resize(
                $needCrop ? $fillImageWidth : $containerWidth,
                $needCrop ? $fillImageHeight : $containerHeight
            );

            if ($needCrop && ($cropTop || $cropLeft || $cropRight || $cropBottom)) {
                $imageObject->crop($cropTop, $cropLeft, $cropRight, $cropBottom);
            }

            try {
                $imageObject->save($imageResized);
            } catch (\Exception $e) {
                $this->logger->critical($e);
                return false;
            }
        } elseif (! $containerWidth || ! $containerHeight) {
            list($containerWidth, $containerHeight) = $this->getSizeFromImage($imageResized);
        }

        $imageUrl = $this->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path . $image;

        return [
            'url'    => $imageUrl,
            'width'  => $containerWidth,
            'height' => $containerHeight
        ];
    }

    /**
     * @param int $containerWidth
     * @param int $containerHeight
     * @param int $originWidth
     * @param int $originHeight
     * @return int[]
     */
    private function getFillImageSize(
        int $containerWidth,
        int $containerHeight,
        int $originWidth,
        int $originHeight
    ) : array {
        $coefficient = max(
            $containerHeight / $originHeight,
            $containerWidth / $originWidth
        );

        return [
            'width' => (int) round($originWidth * $coefficient),
            'height' => (int) round($originHeight * $coefficient),
        ];
    }

    /**
     * @param int $containerWidth
     * @param int $containerHeight
     * @param int $fillImageWidth
     * @param int $fillImageHeight
     * @return array
     */
    private function getFillImageCrop(
        int $containerWidth,
        int $containerHeight,
        int $fillImageWidth,
        int $fillImageHeight
    ) : array {
        $result = [
            'top' => 0,
            'left' => 0,
            'right' => 0,
            'bottom' => 0,
        ];

        if ($fillImageWidth > $containerWidth) {
            $cropWidth = ($fillImageWidth - $containerWidth) / 2;

            $result['left'] = $result['right'] = (int) $cropWidth;
        }

        if ($fillImageHeight > $containerHeight) {
            $cropHeight = ($fillImageHeight - $containerHeight) / 2;

            $result['top'] = $result['bottom'] = (int) $cropHeight;
        }

        return $result;
    }

    /**
     * @param string $pathToImage
     * @return array
     */
    private function getSizeFromImage(string $pathToImage) : array
    {
        /** @var \Magento\Framework\Image\Adapter\Gd2 $imageObject */
        $imageObject = $this->imageFactory->create();
        $imageObject->open($pathToImage);

        return [$imageObject->getOriginalWidth(), $imageObject->getOriginalHeight()];
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|\Magento\Store\Model\Store
     */
    private function getStore()
    {
        return $this->storeManager->getStore();
    }
}
