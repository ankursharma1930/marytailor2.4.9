<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Block\Post;

use Magefan\Blog\Block\Post\View;
use Magefan\BlogExtra\Block\Widget\Contents;
use Magento\Framework\App\RequestInterface;
use Magefan\Blog\Model\Config;

class ViewPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param RequestInterface $request
     * @param Config $config
     */
    public function __construct(
        RequestInterface $request,
        Config $config
    ) {
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * @param View $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetContent(View $subject, $result)
    {
        if ($this->request->getFullActionName() !== 'blog_post_view') {
            return $result;
        }

        $contentsSidebarEnabled = $this->config->getConfig('mfblog/sidebar/contents/enabled');
        $contentWidgetEnabled = (strpos($result, Contents::BLOG_POST_CONTENTS) !== false);

        if (!$contentWidgetEnabled && !$contentsSidebarEnabled) {
            return $result;
        }

        $contentsBlock = $subject->getLayout()->createBlock(\Magefan\BlogExtra\Block\Post\View\Contents::class);

        if ($newContent = $contentsBlock->getUpdatedContent()) {
            $result = $newContent;
        }

        if ($contentWidgetEnabled) {
            $contents = $contentsBlock->setTemplate('Magefan_BlogExtra::widget/contents-php.phtml')->toHtml();
            $result = str_replace(Contents::BLOG_POST_CONTENTS, $contents, $result);
        }

        return $result;
    }
}
