<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\ProductAlert;

class GetInitialPrices
{
    /**
     * @var \Plumrocket\AmpEmail\Model\Component\ProductAlert\InitialAlertPrice
     */
    private $priceSubscriber;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Store\Api\Data\WebsiteInterface[]
     */
    private $websites;

    /**
     * @var \Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory
     */
    private $priceColFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    private $catalogData;

    /**
     * GetInitialPrices constructor.
     *
     * @param \Plumrocket\AmpEmail\Model\Component\ProductAlert\InitialAlertPrice $priceSubscriber
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                  $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface                          $storeManager
     * @param \Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory   $priceColFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface                   $customerRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                     $productRepository
     * @param \Magento\Catalog\Helper\Data                                        $catalogData
     */
    public function __construct(
        InitialAlertPrice $priceSubscriber,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory $priceColFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Data $catalogData
    ) {
        $this->priceSubscriber = $priceSubscriber;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->priceColFactory = $priceColFactory;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->catalogData = $catalogData;
    }

    /**
     * Retrieve website collection array
     *
     * @return array
     * @throws \Exception
     */
    private function getWebsites() : array
    {
        if ($this->websites === null) {
            try {
                $this->websites = $this->storeManager->getWebsites();
            } catch (\Exception $e) {
                throw $e;
            }
        }
        return $this->websites;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function execute()
    {
        $result = [];
        foreach ($this->getWebsites() as $website) {
            /* @var $website \Magento\Store\Model\Website */
            if (! $website->getDefaultGroup() || ! $website->getDefaultGroup()->getDefaultStore()) {
                continue;
            }

            $enabledPriceAlert = $this->scopeConfig->getValue(
                \Magento\ProductAlert\Model\Observer::XML_PATH_PRICE_ALLOW,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $website->getDefaultGroup()->getDefaultStore()->getId()
            );

            if (! $enabledPriceAlert) {
                continue;
            }

            try {
                $collection = $this->priceColFactory->create()->addWebsiteFilter(
                    $website->getId()
                )->setCustomerOrder();
            } catch (\Exception $e) {
                throw $e;
            }

            $previousCustomer = null;
            $this->priceSubscriber->setWebsite($website);
            /** @var \Magento\ProductAlert\Model\Price $alert */

            foreach ($collection as $alert) {
                $this->setAlertStoreId($alert, $this->priceSubscriber);
                try {
                    if (!$previousCustomer || $previousCustomer->getId() != $alert->getCustomerId()) {
                        $customer = $this->customerRepository->getById($alert->getCustomerId());
                        if ($previousCustomer) {
                            $result = $this->priceSubscriber->render($result);
                        }
                        if (! $customer) {
                            continue;
                        }
                        $previousCustomer = $customer;
                        $this->priceSubscriber->clean();
                        $this->priceSubscriber->setWebsite($website);
                        $this->priceSubscriber->setCustomerData($customer);
                    } else {
                        $customer = $previousCustomer;
                    }

                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $this->productRepository->getById(
                        $alert->getProductId(),
                        false,
                        $website->getDefaultStore()->getId()
                    );

                    $product->setCustomerGroupId($customer->getGroupId());
                    if ($alert->getPrice() > $product->getFinalPrice()) {
                        $productPrice = $product->getFinalPrice();
                        $product->setFinalPrice($this->catalogData->getTaxPrice($product, $productPrice));
                        $product->setPrice($this->catalogData->getTaxPrice($product, $product->getPrice()));
                        $this->priceSubscriber->addInitialPrice($product, $alert->getPrice());
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            }

            if ($previousCustomer) {
                try {
                    $result = $this->priceSubscriber->render($result);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }

        return $result;
    }

    /**
     *  Set alert store id.
     *
     * @param \Magento\ProductAlert\Model\Price                                   $alert
     * @param \Plumrocket\AmpEmail\Model\Component\ProductAlert\InitialAlertPrice $priceSubscriber
     * @return $this
     */
    private function setAlertStoreId(
        \Magento\ProductAlert\Model\Price $alert,
        InitialAlertPrice $priceSubscriber
    ) : self {
        $priceSubscriber->setStoreId((int) $alert->getStoreId());

        return $this;
    }
}
