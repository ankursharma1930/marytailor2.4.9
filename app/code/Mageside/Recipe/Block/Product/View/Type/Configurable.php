<?php

namespace Mageside\Recipe\Block\Product\View\Type;

use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Configurable extends \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable
{
    /**
     * @var Format
     */
    private $formatter;

    /**
     * Configurable constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\ConfigurableProduct\Helper\Data $helper
     * @param \Magento\Catalog\Helper\Product $catalogProduct
     * @param CurrentCustomer $currentCustomer
     * @param PriceCurrencyInterface $priceCurrency
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param Format $formatter
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\ConfigurableProduct\Helper\Data $helper,
        \Magento\Catalog\Helper\Product $catalogProduct,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ConfigurableAttributeData $configurableAttributeData,
        Format $formatter,
        array $data = []
    ) {
        $this->formatter = $formatter;
        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $helper,
            $catalogProduct,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $data
        );
    }

    /**
     * @return array
     */
    protected function _getAdditionalConfig()
    {
        return ['containerId' => $this->getBlockSelector() . $this->getProduct()->getId()];
    }

    /**
     * @return string
     */
    public function getPricesJson()
    {
        $priceInfo = $this->getProduct()->getPriceInfo();
        $regularPrice = $priceInfo->getPrice('regular_price');
        $finalPrice = $priceInfo->getPrice('final_price');
        $prices = [
            'oldPrice' => [
                'amount' => $this->formatter->getNumber($regularPrice->getAmount()->getValue()),
            ],
            'basePrice' => [
                'amount' => $this->formatter->getNumber($finalPrice->getAmount()->getBaseAmount()),
            ],
            'finalPrice' => [
                'amount' => $this->formatter->getNumber($finalPrice->getAmount()->getValue()),
            ],
        ];

        return $this->jsonEncoder->encode($prices);
    }

    /**
     * @return string
     */
    public function getPriceFormatJson()
    {
        return $this->jsonEncoder->encode($this->formatter->getPriceFormat());
    }
}
