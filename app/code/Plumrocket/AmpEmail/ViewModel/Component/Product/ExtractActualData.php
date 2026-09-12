<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\ViewModel\Component\Product;

class ExtractActualData
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    private $catalogData;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * ExtractActualData constructor.
     *
     * @param \Magento\Catalog\Api\ProductRepositoryInterface   $productRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Catalog\Helper\Data                      $catalogData
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->catalogData = $catalogData;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Catalog\Api\Data\ProductInterface|int $product
     * @param int                                                                           $customerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($product, int $customerId) : array
    {
        if (! $product instanceof \Magento\Catalog\Model\Product) {
            $product = $this->productRepository->getById($product);
        }

        if ($customerId) {
            try {
                $groupId = $this->customerRepository->getById($customerId)->getGroupId();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $groupId = 0;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $groupId = 0;
            }
        } else {
            $groupId = 0;
        }

        $product->setCustomerGroupId($groupId);

        return $this->extractNecessaryProductData($product);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    private function extractNecessaryProductData(\Magento\Catalog\Model\Product $product) : array
    {
        $isSaleble = $product->getIsSalable();
        $productPrice = $this->catalogData->getTaxPrice($product, $product->getFinalPrice());
        return [
            'isSalable' => $isSaleble,
            'isNotSalable' => ! $isSaleble, // deprecated
            'formattedPrice' => $this->priceCurrency->format($productPrice),
            'price' => $productPrice,
            'name' => $product->getName(),
        ];
    }
}
