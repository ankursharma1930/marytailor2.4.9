<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe;

class GalleryRecipe extends \Magento\Catalog\Block\Product\View\Gallery
{
    /**
     * @var \Magento\ProductVideo\Helper\Media
     */
    protected $_mediaHelper;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param \Magento\ProductVideo\Helper\Media $mediaHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\ProductVideo\Helper\Media $mediaHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $data
        );
        $this->_mediaHelper = $mediaHelper;
    }

    /**
     * Retrieve media gallery data in JSON format
     *
     * @return string
     */
    public function getMediaGalleryDataJson()
    {
        $mediaGalleryData = [];
        $recipe = $this->_coreRegistry->registry('recipe');
            $mediaGalleryData[] = [
                'mediaType' => $recipe->getMediaType(),
                'videoUrl' => $recipe->getMediaTypeVideoUrl(),
                'isBase' => true,
            ];

            return $this->jsonEncoder->encode($mediaGalleryData);
    }

    /**
     * Retrieve video settings data in JSON format
     *
     * @return string
     */
    public function getVideoSettingsJson()
    {
        $videoSettingData[] = [
            'playIfBase' => 0,
            'showRelated' => 0,
            'videoAutoRestart' => 0,
        ];
        return $this->jsonEncoder->encode($videoSettingData);
    }

    /**
     * Return media gallery for product options
     * @return string
     */
    public function getOptionsMediaGalleryDataJson()
    {
        return  $this->jsonEncoder->encode([]);
    }
}
