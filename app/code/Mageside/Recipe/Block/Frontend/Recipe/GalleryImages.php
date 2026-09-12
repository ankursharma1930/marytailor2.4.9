<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe;

use Magento\Catalog\Helper\Image;

class GalleryImages extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Mageside\Recipe\Model\FileUploader
     */
    protected $fileUploader;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var \Magento\Framework\Config\View
     */
    protected $configView;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * GalleryImages constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     * @param \Magento\Framework\Registry $registry
     * @param Image $imageHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Mageside\Recipe\Model\FileUploader $fileUploader,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Image $imageHelper,
        array $data = []
    ) {
        $this->fileUploader = $fileUploader;
        $this->jsonEncoder = $jsonEncoder;
        $this->coreRegistry = $registry;
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $data);
    }

    public function getImageUrl()
    {
        $imagePath = $this->fileUploader->getBaseUrl() . $this->fileUploader->getBasePath();

        return $imagePath;
    }

    /**
     * Return breakpoints options
     *
     * @return string
     */
    public function getBreakpoints()
    {
        return $this->jsonEncoder->encode($this->getVar('breakpoints'));
    }

    /**
     * Return magnifier options
     *
     * @return string
     */
    public function getMagnifier()
    {
        return $this->jsonEncoder->encode($this->getVar('magnifier'));
    }

    /**
     * Retrieve product images in JSON format
     *
     * @return string
     */
    public function getGalleryImagesJson()
    {
        $imageItem = false;
        $recipe = $this->coreRegistry->registry('recipe');

        if ($recipe->getMediaType() == 'image') {
            $imageUrl = $recipe->getMediaTypeImage() ? $this->getImageUrl() . '/'
                . $recipe->getMediaTypeImage() : false;
            if ($imageUrl) {
                $imageItem = [
                    'thumb'     => $imageUrl,
                    'img'       => $imageUrl,
                    'full'      => $imageUrl,
                    'caption'   => $recipe->getTitle(),
                    'position'  => '1',
                    'type'      => 'image',
                    'isMain'    => true,
                    'videoUrl'  => null
                ];
            }
        } else {
            $imageData = $this->getVideoImage();
            if (isset($imageData['img'])) {
                $imageItem = [
                    'thumb'     => $imageData['thumb'],
                    'img'       => $imageData['img'],
                    'full'      => $imageData['img'],
                    'caption'   => __('Video'),
                    'position'  => '1',
                    'type'      => 'video',
                    'isMain'    => true,
                    'videoUrl'  => $recipe->getMediaTypeVideoUrl()
                ];
            }
        }

        if ($imageItem) {
            $imagesItems = [$imageItem];
        } else {
            $imagesItems = [
                [
                    'thumb'     => $this->imageHelper->getDefaultPlaceholderUrl('thumbnail'),
                    'img'       => $this->imageHelper->getDefaultPlaceholderUrl('image'),
                    'full'      => $this->imageHelper->getDefaultPlaceholderUrl('image'),
                    'caption'   => $recipe->getTitle(),
                    'position'  => '1',
                    'type'      => 'image',
                    'isMain'    => true,
                    'videoUrl'  => null
                ]
            ];
        }

        return json_encode($imagesItems);
    }

    public function getVideoImage()
    {
        $recipe = $this->coreRegistry->registry('recipe');
        $videoUrl = $recipe->getMediaTypeVideoUrl();
        if (preg_match('/youtube\.com|youtu\.be/', $videoUrl)) {
            $video_id_array = explode("=", $videoUrl);
            if (isset($video_id_array[1])) {
                $imageData['thumb'] = "https://img.youtube.com/vi/$video_id_array[1]/default.jpg";
                $imageData['img'] = "https://img.youtube.com/vi/$video_id_array[1]/hqdefault.jpg";

                return $imageData;
            }
        } elseif (preg_match('/vimeo\.com/', $videoUrl)) {
            $video_id_array = explode("/", $videoUrl);
            if (isset($video_id_array[3])) {
                $image = file_get_contents("http://vimeo.com/api/v2/video/$video_id_array[3].json");
                $decodeImg = json_decode($image);
                if (isset($decodeImg[0])) {
                    $imgObject = $decodeImg[0];
                    $imageData['img'] = $imgObject->thumbnail_large;
                    $imageData['thumb'] = $imgObject->thumbnail_small;

                    return $imageData;
                }
            }
        }

        return '';
    }

    public function getImageAttribute($imageId, $attributeName, $default = null)
    {
        $attributes = $this->getConfigView()
            ->getMediaAttributes('Mageside_Recipe', Image::MEDIA_TYPE_CONFIG_NODE, $imageId);

        return isset($attributes[$attributeName]) ? $attributes[$attributeName] : $default;
    }

    private function getConfigView()
    {
        if (!$this->configView) {
            $this->configView = $this->_viewConfig->getViewConfig();
        }
        return $this->configView;
    }

    public function returnImageUrl(){
        $recipe = $this->coreRegistry->registry('recipe');
        $image = $recipe->getMediaTypeImage() ? $this->getImageUrl() . '/'
            . $recipe->getMediaTypeImage() : false;

        return $image;
    }
}
