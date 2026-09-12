<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Component\Product;

use Plumrocket\AmpEmailApi\Block\ProductListComponentInterface;

/**
 *
 * @method getPeriod()
 * @method getProductsCount()
 */
class RelatedProducts extends \Plumrocket\AmpEmailApi\Block\AbstractProductComponent implements
    ProductListComponentInterface
{
    /**
     * @var string
     */
    protected $styleFileId = 'Plumrocket_AmpEmail::css/component/:version/product/amp-carousel.css';

    /**
     * @var \Plumrocket\AmpEmail\ViewModel\Component\Sales\Order\GetOrder
     */
    private $getOrder;

    /**
     * RelatedProducts constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context              $context
     * @param \Magento\Framework\Url                                        $frontUrlBuilder
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface     $componentDataLocator
     * @param \Magento\Framework\View\Asset\Repository                      $viewAssetRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface               $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface             $priceCurrency
     * @param \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface    $initFrontProductPrice
     * @param \Plumrocket\AmpEmail\ViewModel\Component\Sales\Order\GetOrder $getOrder
     * @param array                                                         $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url $frontUrlBuilder,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Magento\Framework\View\Asset\Repository $viewAssetRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface $initFrontProductPrice,
        \Plumrocket\AmpEmail\ViewModel\Component\Sales\Order\GetOrder $getOrder,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $frontUrlBuilder,
            $componentDataLocator,
            $viewAssetRepository,
            $productRepository,
            $priceCurrency,
            $initFrontProductPrice,
            $data
        );
        $this->getOrder = $getOrder;
    }

    /**
     * @return string
     */
    public function getListUrl() : string
    {
        return $this->getAmpApiUrl(
            'amp-email-api/V1/product_related',
            [
                'order_id' => (int) $this->getOrder()->getId(),
                'count' => $this->getProductsCount(),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function _toHtml()
    {
        if (false === $this->getOrder()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return false|\Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        return $this->getOrder->execute($this);
    }
}
