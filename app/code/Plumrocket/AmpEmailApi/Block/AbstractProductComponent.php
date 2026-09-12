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

use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class AbstractProductComponent
 *
 * @package Plumrocket\AmpEmailApi\Block
 */
abstract class AbstractProductComponent extends \Plumrocket\AmpEmailApi\Block\AbstractComponent
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface
     */
    private $initFrontProductPrice;

    /**
     * AbstractProductComponent constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context           $context
     * @param \Magento\Framework\Url                                     $frontUrlBuilder
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface  $componentDataLocator
     * @param \Magento\Framework\View\Asset\Repository                   $viewAssetRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface            $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface          $priceCurrency
     * @param \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface $initFrontProductPrice
     * @param array                                                      $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url $frontUrlBuilder,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Magento\Framework\View\Asset\Repository $viewAssetRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface $initFrontProductPrice,
        array $data = []
    ) {
        parent::__construct($context, $frontUrlBuilder, $componentDataLocator, $viewAssetRepository, $data);
        $this->productRepository = $productRepository;
        $this->priceCurrency = $priceCurrency;
        $this->initFrontProductPrice = $initFrontProductPrice;
    }

    /**
     * Set states in needed
     *
     * @param $html
     * @return string
     */
    protected function _afterToHtml($html) //@codingStandardsIgnoreLine
    {
        if ($this->canShowAddToCart()) {
            $this->ampStates['cart'] = [];
        }

        if ($this->canShowWishlist()) {
            $this->ampStates['wishlist'] = [];
        }

        return parent::_afterToHtml($html);
    }

    /**
     * @return bool
     */
    public function canShowWishlist() : bool
    {
        return in_array('add_to_wishlist', explode(',', $this->getShowButtons()), true);
    }

    /**
     * @return bool
     */
    public function canShowAddToCart() : bool
    {
        return in_array('add_to_cart', explode(',', $this->getShowButtons()), true);
    }

    /**
     * @param string $attr
     * @return bool
     */
    public function canShowAttr(string $attr) : bool
    {
        if (empty($this->getShowAttributes())) {
            return true; //show if parameter nor defined
        }

        return in_array($attr, explode(',', $this->getShowAttributes()), true);
    }

    /**
     * @return string
     */
    public function getShowButtons() : string
    {
        return (string) $this->_getData('show_buttons');
    }

    /**
     * @return string
     */
    public function getShowAttributes() : string
    {
        return (string) $this->_getData('show_attributes');
    }

    /**
     * @return string
     */
    public function getCheckoutUrl() : string
    {
        return $this->getFrontUrl(
            'amp-email-api/customer/login',
            [
                'token' => $this->getComponentDataLocator()->getToken(),
                'store' => $this->getComponentDataLocator()->getStoreId(),
                '_nosid' => 1
            ]
        );
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|bool
     */
    public function getProduct()
    {
        $productSku = $this->getProductSku();
        $productId = $this->getProductId();
        $product = $this->_getData('product');

        switch (true) {
            case $product:
                if ($product instanceof ProductInterface || ! is_numeric($product)) {
                    break;
                }
                $productId = (int) $product; // no break
            case $productId:
                try {
                    $product = $this->productRepository->getById($productId);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $product = false;
                }
                break;
            case $productSku:
                try {
                    $product = $this->productRepository->get($productSku);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $product = false;
                }
                break;
            default:
                $product = false;
        }

        if ($product) {
            $product = $this->initFrontProductPrice->execute($product, $this->getComponentDataLocator());
        }

        return $product;
    }

    /**
     * @param      $amount
     * @param bool $includeContainer
     * @param      $precision
     * @param null $scope
     * @param null $currency
     * @return float|string
     */
    public function formatPrice(
        $amount,
        bool $includeContainer = true,
        int $precision = \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
        $scope = null,
        $currency = null
    ) {
        return $this->priceCurrency->format($amount, $includeContainer, $precision, $scope, $currency);
    }
}
