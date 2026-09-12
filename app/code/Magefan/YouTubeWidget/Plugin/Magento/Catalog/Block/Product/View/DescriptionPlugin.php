<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\YouTubeWidget\Plugin\Magento\Catalog\Block\Product\View;

use Magento\Catalog\Block\Product\View\Description;
use Magento\Catalog\Helper\Image;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class DescriptionPlugin
{

    const REPLACE_YOUTUBE_IFRAME_PATH = 'mfyoutube/general/replace_youtube_iframes';
    const REPLACE_YOUTUBE_IFRAME_IMAGE_QUALITY_PATH = 'mfyoutube/general/replace_youtube_iframes_image_quality';

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var ScopeConfigInterface
     */
    private  $scopeConfig;

    /**
     * @param Http $request
     * @param Image $imageHelper
     * @param Registry $registry
     */
    public function __construct(
        Http $request,
        Image $imageHelper,
        Registry $registry,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->registry = $registry;
        $this->imageHelper = $imageHelper;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Description $subject
     * @param $result
     * @return array|mixed|string|string[]
     */
    public function afterToHtml(Description $subject, $result) {
        $productPage = $this->request->getFullActionName();
        $replaceYoutubeIframe = (bool)$this->scopeConfig->getValue(
            self::REPLACE_YOUTUBE_IFRAME_PATH,
            ScopeInterface::SCOPE_STORE
        );
        if ($productPage === 'catalog_product_view' && $replaceYoutubeIframe) {

            $result = $this->_replaceHtml($subject, $result);
        }
        return $result;
    }

    /**
     * @param $subject
     * @param $html
     * @return array|mixed|string|string[]
     */
    protected function _replaceHtml($subject, $html)
    {
        $pattern = '/<iframe.+?youtube\.com\/embed\/(.*?)(?:\?|").+?<\/iframe>/';
        $matches = array();
        $success = preg_match_all($pattern, $html, $matches);
        if ($success && !empty($matches[0]) && !empty($matches[1])) {
            foreach ($matches[0] as $i => $iframeHtml) {
                $blockHtml = $subject->getLayout()->createBlock(
                    \Magefan\YouTubeWidget\Block\Widget\YouTube::class
                )
                    ->setData('video_url', 'https://www.youtube.com/watch?v=' . $matches[1][$i])
                    ->setData('image_quality',
                        $this->scopeConfig->getValue(
                            self::REPLACE_YOUTUBE_IFRAME_IMAGE_QUALITY_PATH,
                            ScopeInterface::SCOPE_STORE
                        )
                    )
                    ->toHtml();
                $html = str_replace($iframeHtml, $blockHtml, $html);
            }
        }

        return $html;
    }
}
