<?php

namespace Dolphin\Productfaq\Block\Adminhtml\Productfaq\Edit;

use Magento\Backend\Block\Widget\Context;

abstract class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Context
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Return model ID
     *
     * @return int|null
     */
    public function getModelId()
    {
        return $this->context->getRequest()->getParam('productfaq_id');
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
        return $this->urlBuilder->getUrl($route, $params);
    }
}
