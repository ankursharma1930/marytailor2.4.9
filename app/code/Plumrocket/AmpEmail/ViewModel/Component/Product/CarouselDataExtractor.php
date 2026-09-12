<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\ViewModel\Component\Product;

class CarouselDataExtractor
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * TODO: replace with ImageFactory after left support magento v2.2
     *
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    private $imageBuilder;

    /**
     * @var \Plumrocket\AmpEmail\Api\CanOneClickAddToCartInterface
     */
    private $canOneClickAddToCart;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    private $productVisibility;

    /**
     * CarouselDataExtractor constructor.
     *
     * @param \Magento\Catalog\Api\ProductRepositoryInterface        $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder           $searchCriteriaBuilder
     * @param \Magento\Framework\Escaper                             $escaper
     * @param \Magento\Catalog\Block\Product\ImageBuilder            $imageBuilder
     * @param \Plumrocket\AmpEmail\Api\CanOneClickAddToCartInterface $canOneClickAddToCart
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface      $priceCurrency
     * @param \Magento\Catalog\Model\Product\Visibility              $productVisibility
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Plumrocket\AmpEmail\Api\CanOneClickAddToCartInterface $canOneClickAddToCart,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Product\Visibility $productVisibility
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->escaper = $escaper;
        $this->imageBuilder = $imageBuilder;
        $this->canOneClickAddToCart = $canOneClickAddToCart;
        $this->priceCurrency = $priceCurrency;
        $this->productVisibility = $productVisibility;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface[]|int[] $products
     * @param int                                                $customerId
     * @return array
     */
    public function execute(array $products, $customerId = 0) : array
    {
        if (empty($products)) {
            return [];
        }

        $products = $this->createProductsFromIds($products);

        $productsInfo = $this->extractNecessaryProductData($products);

        return $this->addGeneralData($productsInfo, $customerId);
    }

    /**
     * @param \Magento\Catalog\Model\Product[] $products
     * @return array
     */
    private function extractNecessaryProductData(array $products) : array
    {
        $result = [];
        $visibleStatuses = $this->productVisibility->getVisibleInSiteIds();
        foreach ($products as $product) {
            $isCanOneClickAddToCart = $this->canOneClickAddToCart->execute($product);
            $prices = $this->getProductPrices($product);

            $result[] = [
                'id' => $product->getId(),
                'woOptions' => $isCanOneClickAddToCart,
                'withOptions' => ! $isCanOneClickAddToCart,
                'url' => $this->escaper->escapeUrl($product->getProductUrl()),
                'name' => $this->escaper->escapeHtml($product->getName()),
                'price' => $this->priceCurrency->format($prices['price']),
                'specialPrice' => $prices['specialPrice'] ? $this->priceCurrency->format($prices['specialPrice']) : '',
                'imageUrl' => $this->imageBuilder->create($product, 'pr_amp_email_sliders')->getImageUrl(),
                'alt' => $this->escaper->escapeHtmlAttr($product->getName()),
                'isVisibleInSite' => in_array((int) $product->getVisibility(), $visibleStatuses, true),
            ];
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface[]|int[] $products
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private function createProductsFromIds(array $products) : array
    {
        $productWithoutKeys = array_values($products);
        if (is_numeric($productWithoutKeys[0])) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('entity_id', $products, 'in')
                ->create();
            $searchResult = $this->productRepository->getList($searchCriteria);

            $products = $searchResult->getItems();
        }

        return $products;
    }

    /**
     * @param array $productsInfo
     * @param       $customerId
     * @return array
     */
    private function addGeneralData(array $productsInfo, $customerId) : array
    {
        $generalData = [];
        $generalData['isGuest'] = ! $customerId;
        $generalData['isCustomer'] = (bool) $customerId;

        return array_map(static function (array $item) use ($generalData) {
            return array_merge($item, $generalData);
        }, $productsInfo);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    private function getProductPrices(\Magento\Catalog\Model\Product $product) : array
    {
        switch ($product->getTypeId()) {
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $basePrice = $product->getPriceInfo()->getPrice('regular_price');
                $regularPrice = $basePrice->getMinRegularAmount()->getValue();
                $specialPrice = $product->getFinalPrice();
                break;
            case \Magento\Bundle\Model\Product\Type::TYPE_CODE:
                $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
                $specialPrice = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
                break;
            case \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE:
                $usedProds = $product->getTypeInstance()->getAssociatedProducts($product);
                $regularPrice = 0;
                $specialPrice = 0;
                foreach ($usedProds as $child) {
                    if ((int) $child->getId() !== (int) $product->getId()) {
                        $regularPrice += $child->getPrice();
                        $specialPrice += $child->getFinalPrice();
                    }
                }
                break;
            default:
                $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
                $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();
                break;
        }

        return [
            'price'        => $regularPrice,
            'specialPrice' => (float) $specialPrice !== (float) $regularPrice ? $specialPrice : 0,
        ];
    }
}
