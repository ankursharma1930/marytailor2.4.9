<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Block\Post\View;

use Magefan\Blog\Model\Post;
use Magefan\Blog\Model\PostFactory;
use Magefan\Blog\Model\Url;
use Magefan\BlogExtra\Model\Config;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;

/**
 * Blog progress bar
 */
class ReadingProgressBar extends \Magefan\Blog\Block\Post\AbstractPost
{
    /**
     * @var Config
     */
    private $extraConfig;

    /**
     * @param Config $extraConfig
     * @param Context $context
     * @param Post $post
     * @param Registry $coreRegistry
     * @param FilterProvider $filterProvider
     * @param PostFactory $postFactory
     * @param Url $url
     * @param array $data
     * @param $config
     * @param $templatePool
     */
    public function __construct(
        Config         $extraConfig,
        Context        $context,
        Post           $post,
        Registry       $coreRegistry,
        FilterProvider $filterProvider,
        PostFactory    $postFactory,
        Url            $url,
        array          $data = [],
                       $config = null,
                       $templatePool = null
    )
    {
        $this->extraConfig = $extraConfig;
        parent::__construct($context, $post, $coreRegistry, $filterProvider, $postFactory, $url, $data, $config, $templatePool);
    }

    /**
     * @param $data
     * @return string
     */
    public function getReadingProgressBarConfigData($data)
    {
        return $this->extraConfig->getReadingProgressBarConfigData($data);
    }
}

