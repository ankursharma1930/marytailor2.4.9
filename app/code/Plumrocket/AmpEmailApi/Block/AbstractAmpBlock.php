<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_AmpEmailApi
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmailApi\Block;

abstract class AbstractAmpBlock extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Url
     */
    private $frontUrlBuilder;

    /**
     * @var \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    private $componentDataLocator;

    /**
     * AbstractAmpBlock constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context          $context
     * @param \Magento\Framework\Url                                    $frontUrlBuilder
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator
     * @param array                                                     $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url $frontUrlBuilder,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->frontUrlBuilder = $frontUrlBuilder;
        $this->componentDataLocator = $componentDataLocator;
    }

    /**
     * Retrieve url for component's lists and forms
     *
     * @param null $routePath
     * @param null $routeParams
     * @return string
     */
    public function getAmpApiUrl($routePath = null, $routeParams = null) : string
    {
        $routeParams = array_merge(
            [
                'token' => $this->getComponentDataLocator()->getToken(),
                'store' => $this->getComponentDataLocator()->getStoreId(),
                'isAjax' => 1,
                '_nosid' => 1
            ],
            $routeParams ?? []
        );

        return $this->frontUrlBuilder->getUrl($routePath, $routeParams);
    }

    /**
     * Retrieve frontend url same in Block
     *
     * @param string $route
     * @param array  $params
     * @return string
     */
    public function getFrontUrl($route = '', $params = []) : string
    {
        return $this->frontUrlBuilder->getUrl($route, $params);
    }

    /**
     * @return \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    public function getComponentDataLocator() : \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
    {
        return $this->componentDataLocator;
    }
}
