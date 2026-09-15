<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Block\Frontend\Recipe;

class SocialMetadata extends \Mageside\Recipe\Block\Frontend\AbstractBlock
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Mageside\Recipe\Helper\Config
     */
    protected $helper;

    /**
     * SocialMetadata constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     * @param \Mageside\Recipe\Helper\Config $helper
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Mageside\Recipe\Model\FileUploader $fileUploader,
        \Mageside\Recipe\Helper\Config $helper
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->helper = $helper;
        parent::__construct($context, $fileUploader, $helper);
    }

    /**
     * @return mixed
     */
    public function getRecipe()
    {
        return $this->_coreRegistry->registry('recipe');
    }

    /**
     * @return array|null
     */
    public function getSocialMetadata()
    {
        $recipe = $this->getRecipe();

        if ($recipe) {
            // og:url / twitter:url must be the plain absolute URL, not urlencoded
            $url = $this->helper->getRecipeUrl($recipe);

            return $result = [
                "recipe_url" => $url,
                "title" => $recipe->getTitle(),
                "image_url" => ($recipe->getThumbnail()) ? $this->fileUploader->getFileWebUrl($recipe->getThumbnail()) : '',
                "short_description" => $recipe->getShortDescriptionHtml(),
            ];
        }

        return null;
    }
}
