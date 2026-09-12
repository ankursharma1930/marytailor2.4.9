<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Controller\V1\ProductAlert\Unsubscribe;

use Magento\Framework\Exception\NoSuchEntityException;

class Price extends \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\ProductAlert\Model\PriceFactory
     */
    private $priceAlertFactory;

    /**
     * Price constructor.
     *
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Magento\Store\Model\App\Emulation                   $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory     $ampJsonFactory
     * @param \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface      $corsValidator
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface      $productRepository
     * @param \Psr\Log\LoggerInterface                             $logger
     * @param \Magento\ProductAlert\Model\PriceFactory             $priceAlertFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory $ampJsonFactory,
        \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface $corsValidator,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\ProductAlert\Model\PriceFactory $priceAlertFactory
    ) {
        parent::__construct($context, $appEmulation, $storeManager, $ampJsonFactory, $corsValidator, $tokenRepository);
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->priceAlertFactory = $priceAlertFactory;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    public function execute()
    {
        $ampJsonResult = $this->ampJsonFactory->create();
        $this->startEmulationForAmp();

        $productId = (int) $this->getRequest()->getParam('product');
        if (! $productId) {
            $this->logger->debug(
                'AmpEmail::product price alert unsubscribe - "product" is a required parameter and is not set.'
            );
            $ampJsonResult->addErrorMessage('"product" is a required parameter and is not set.');
            $this->stopEmulation();
            return $ampJsonResult;
        }

        try {
            /* @var $product \Magento\Catalog\Model\Product */
            $product = $this->productRepository->getById($productId);
            if (!$product->isVisibleInCatalog()) {
                throw new NoSuchEntityException();
            }

            /** @var \Magento\ProductAlert\Model\Price $model */
            $model = $this->priceAlertFactory->create();
            $model->setCustomerId($this->getTokenModel()->getCustomerId())
                ->setProductId($product->getId())
                ->setWebsiteId($this->storeManager->getStore()->getWebsiteId())
                ->setStoreId($this->storeManager->getStore()->getId())
                ->loadByParam();

            if ($model->getId()) {
                $model->delete();
                $ampJsonResult->addSuccessMessage(__('You have successfully unsubscribed from this alert.'));
            } else {
                $ampJsonResult->addSuccessMessage(__('You had previously unsubscribed from this alert.'));
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $ampJsonResult->addErrorMessage(__("The product wasn't found. Verify the product and try again."));
        } catch (\Exception $e) {
            $ampJsonResult->addExceptionMessage(
                $e,
                __('Unable to update the alert subscription. Please try again later.')
            );
        }

        $this->stopEmulation();
        return $ampJsonResult;
    }
}
