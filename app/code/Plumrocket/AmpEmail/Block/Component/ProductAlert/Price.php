<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Component\ProductAlert;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\View\Element\BlockInterface;

/**
 * @method getProductTemplate()
 * @method getProductShowButtons()
 * @method getProductShowAttributes()
 * @method getProductVersion()
 */
class Price extends \Plumrocket\AmpEmailApi\Block\AbstractComponent
{
    /**
     * @var \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface
     */
    private $componentProductLocator;

    /**
     * @var string
     */
    protected $styleFileId = 'Plumrocket_AmpEmail::css/component/:version/product_alert/price.css';

    /**
     * @var \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface
     */
    private $componentPriceAlertLocator;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * @var \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface
     */
    private $initFrontProductPrice;

    /**
     * Price constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context             $context
     * @param \Magento\Framework\Url                                       $frontUrlBuilder
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface    $componentDataLocator
     * @param \Magento\Framework\View\Asset\Repository                     $viewAssetRepository
     * @param \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface    $componentProductLocator
     * @param \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface $componentPriceAlertLocator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface            $priceCurrency
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface         $localeDate
     * @param \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface   $initFrontProductPrice
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url $frontUrlBuilder,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Magento\Framework\View\Asset\Repository $viewAssetRepository,
        \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface $componentProductLocator,
        \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface $componentPriceAlertLocator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface $initFrontProductPrice,
        array $data = []
    ) {
        parent::__construct($context, $frontUrlBuilder, $componentDataLocator, $viewAssetRepository, $data);
        $this->componentProductLocator = $componentProductLocator;
        $this->componentPriceAlertLocator = $componentPriceAlertLocator;
        $this->priceCurrency = $priceCurrency;
        $this->localeDate = $localeDate;
        $this->initFrontProductPrice = $initFrontProductPrice;
    }

    /**
     * @return array|\Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getProducts() : array
    {
        return $this->componentProductLocator->getProducts();
    }

    /**
     * @param int $productId
     * @return float|int
     */
    public function getInitialPrice(int $productId)
    {
        try {
            return $this->componentPriceAlertLocator->getInitialPrice($productId);
        } catch (\Magento\Framework\Exception\NotFoundException $e) {
            return 0;
        }
    }

    /**
     * Url for check actual difference in price
     *
     * @param int $productId
     * @param     $initialPrice
     * @return string
     */
    public function getActualPriceUrl(int $productId, $initialPrice) : string
    {
        return $this->getAmpApiUrl(
            'amp-email-api/V1/productAlert_actual_difference',
            [
                'product' => $productId,
                'initialPrice' => $initialPrice
            ]
        );
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return \Plumrocket\AmpEmail\Block\Component\Product\View
     */
    public function createProductBlock(\Magento\Catalog\Api\Data\ProductInterface $product) : BlockInterface
    {
        /** @var \Plumrocket\AmpEmail\Block\Component\Product\View $component */
        $component = $this->getLayout()->createBlock(\Plumrocket\AmpEmail\Block\Component\Product\View::class);
        $component->setData(
            [
                'product'         => $product,
                'show_attributes' => $this->getProductShowAttributes(),
                'show_buttons'    => $this->getProductShowButtons(),
                'version'         => $this->getProductVersion(),
            ]
        );
        $component->setTemplate($this->getProductTemplate());
        $component->setComponentPartsCollector($this->getComponentPartsCollector());

        return $component;
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

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product
     */
    public function initFrontPrice(ProductInterface $product) : ProductInterface
    {
        return $this->initFrontProductPrice->execute($product, $this->getComponentDataLocator());
    }

    /**
     * Retrieve unsubscribe url for product
     *
     * @param int $productId
     * @return string
     */
    public function getProductUnsubscribeUrl($productId) : string
    {
        $params['product'] = $productId;
        return $this->getAmpApiUrl('amp-email-api/V1/productAlert_unsubscribe_price', $params);
    }

    /**
     * Retrieve unsubscribe url for all products
     *
     * @return string
     */
    public function getUnsubscribeUrl() : string
    {
        return $this->getAmpApiUrl('amp-email-api/V1/productAlert_unsubscribe_priceAll');
    }

    /**
     * @return string
     */
    public function getCurrentDate() : string
    {
        return $this->localeDate->formatDateTime(
            null,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL
        );
    }
}
