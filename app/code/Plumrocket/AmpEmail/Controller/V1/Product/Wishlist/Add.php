<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Controller\V1\Product\Wishlist;

use Magento\Framework\Exception\NoSuchEntityException;

class Add extends \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
{
    /**
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $customerResource;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Add constructor.
     *
     * @param \Magento\Framework\App\Action\Context             $context
     * @param \Magento\Store\Model\App\Emulation                $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory  $ampJsonFactory
     * @param \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface   $corsValidator
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository
     * @param \Magento\Wishlist\Model\WishlistFactory           $wishlistFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer    $customerResource
     * @param \Magento\Catalog\Api\ProductRepositoryInterface   $productRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory $ampJsonFactory,
        \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface $corsValidator,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context, $appEmulation, $storeManager, $ampJsonFactory, $corsValidator, $tokenRepository);
        $this->wishlistFactory = $wishlistFactory;
        $this->customerResource = $customerResource;
        $this->productRepository = $productRepository;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    public function execute()
    {
        $ampJsonResult = $this->ampJsonFactory->create();

        $this->startEmulationForAmp();

        $customerId = $this->getTokenModel()->getCustomerId();
        $productId = (int) str_replace('p', '', $this->getRequest()->getParam('product'));

        if (! $customerId || ! $this->customerResource->checkCustomerId($customerId)) {
            return $ampJsonResult->addErrorMessage(__('We can\'t specify a customer.'));
        }

        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            $product = null;
        }

        if (! $product || ! $product->isVisibleInCatalog()) {
            return $ampJsonResult->addErrorMessage(__('We can\'t specify a product.'));
        }

        try {
            /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
            $wishlist = $this->wishlistFactory->create();
            $wishlist->loadByCustomerId($customerId, true);

            $result = $wishlist->addNewItem($product);
            if (is_string($result)) {
                throw new \Magento\Framework\Exception\LocalizedException(__($result));
            }
            if ($wishlist->isObjectNew()) {
                $wishlist->save();
            }
            $this->_eventManager->dispatch(
                'wishlist_add_product',
                ['wishlist' => $wishlist, 'product' => $product, 'item' => $result]
            );

            // Dont calculate count of wishlist items and put value to customer session
            // because this is api and customer hasn't session
            $ampJsonResult->addSuccessMessage(__('Added to Wishlist'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) { //@codingStandardsIgnoreLine
            $ampJsonResult->addErrorMessage(
                __('We can\'t add the item to Wish List right now: %1.', $e->getMessage())
            );
        } catch (\Exception $e) {
            $ampJsonResult->addExceptionMessage(
                $e,
                __('We can\'t add the item to Wish List right now.')
            );
        }

        $this->stopEmulation();

        return $ampJsonResult;
    }
}
