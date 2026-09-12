<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Adminhtml\Edit;

/**
 * Class GenericButton
 */
class GenericButton
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    private $_urlBuilder;

    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    private $_registry;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $_request;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_request = $context->getRequest();
        $this->_registry = $registry;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    /**
     * Generate url by route and parameters
     *
     * @return  string
     */
    public function getBaseUrl()
    {
        return $this->_urlBuilder->getBaseUrl();
    }

    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    public function getRequest()
    {
        return $this->_request;
    }
}
