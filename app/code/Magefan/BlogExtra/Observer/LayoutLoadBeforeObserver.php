<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magefan\BlogExtra\Model\Config;
use Magento\Framework\App\RequestInterface;

/**
 * Class LayoutLoadBeforeObserver
 */
class LayoutLoadBeforeObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * LayoutLoadBeforeObserver constructor.
     * @param Config $config
     * @param RequestInterface $request
     */
    public function __construct(
        Config $config,
        RequestInterface $request
    ) {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->config->isEnabled() && $this->config->isBlogSearchEnabled()) {
            if ($this->request->getParam('type') == 'blog'
                && $this->request->getFullActionName() == 'catalogsearch_result_index'
            ) {
                $layout = $observer->getLayout();
                $layout->getUpdate()->addHandle('blog_catalogsearch_result_index');
            }
        }
    }
}
