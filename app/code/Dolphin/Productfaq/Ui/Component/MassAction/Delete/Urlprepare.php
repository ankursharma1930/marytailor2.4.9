<?php

namespace Dolphin\Productfaq\Ui\Component\MassAction\Delete;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Action;

class Urlprepare extends Action
{
    /**
     * URL builder instance for generating URLs.
     *
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * HTTP request instance for handling HTTP requests.
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * Constructor function to initialize dependencies.
     *
     * @param ContextInterface $context
     * @param RequestInterface $request
     * @param UrlInterface     $urlBuilder
     * @param array            $components
     * @param array            $data
     * @param array            $actions
     */
    public function __construct(
        ContextInterface $context,
        RequestInterface $request,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $actions = []
    ) {
        parent::__construct($context, $components, $data, $actions);
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepares the block for rendering.
     *
     * @return void
     */
    public function prepare()
    {
        parent::prepare();
        $config = $this->getConfiguration();
        $config['url'] = $this->urlBuilder->getUrl($config['urlPath']);
        $this->setData('config', $config);
    }
}
