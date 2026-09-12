<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Controller\V1\Product;

use Magento\Framework\Exception\NoSuchEntityException;

class Add extends \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * TODO: refactor code after left support 2.2
     *
     * @var \Magento\Checkout\Model\Cart
     */
    private $customerCart;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * Add constructor.
     *
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Magento\Store\Model\App\Emulation                   $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory     $ampJsonFactory
     * @param \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface      $corsValidator
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface      $productRepository
     * @param \Magento\Customer\Model\CustomerRegistry             $customerRegistry
     * @param \Magento\Customer\Model\Session                      $customerSession
     * @param \Psr\Log\LoggerInterface                             $logger
     * @param \Magento\Checkout\Model\Cart                         $customerCart
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory $ampJsonFactory,
        \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface $corsValidator,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Cart $customerCart,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
    ) {
        parent::__construct($context, $appEmulation, $storeManager, $ampJsonFactory, $corsValidator, $tokenRepository);
        $this->productRepository = $productRepository;
        $this->customerRegistry = $customerRegistry;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->customerCart = $customerCart;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    public function execute()
    {
        $ampJsonResult = $this->ampJsonFactory->create();

        $this->startEmulationForAmp();

        $customer = $this->initCustomer();
        if (! $customer) {
            return $ampJsonResult->addErrorMessage(__('We can\'t specify a customer.'));
        }

        $product = $this->initProduct();
        if (! $product || ! $product->isVisibleInCatalog()) {
            return $ampJsonResult->addErrorMessage(__('We can\'t specify a product.'));
        }

        $params['qty'] = 1;

        $this->customerSession->setCustomerAsLoggedIn($customer);

        try {
            $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());

            $this->customerCart->addProduct($product, $stockItem->getMinSaleQty());
            $this->customerCart->save();
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return $ampJsonResult->addErrorMessage($e->getMessage());
        }

        $this->stopEmulation();

        return $ampJsonResult->addSuccessMessage(__('You added %1 to your shopping cart.', $product->getName()));
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product|\Magento\Catalog\Api\Data\ProductInterface|false
     */
    private function initProduct()
    {
        $productId = (int) str_replace('p', '', $this->getRequest()->getParam('product'));
        if ($productId) {
            try {
                return $this->productRepository->getById($productId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Initialize customer instance from request data
     *
     * @return \Magento\Customer\Model\Customer|false
     */
    private function initCustomer()
    {
        $customerId = $this->getTokenModel()->getCustomerId();
        if ($customerId) {
            try {
                return $this->customerRegistry->retrieve($customerId);
            } catch (NoSuchEntityException $e) {
                return false;
            } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
                $this->logger->error($localizedException);
                return false;
            }
        }
        return false;
    }
}
