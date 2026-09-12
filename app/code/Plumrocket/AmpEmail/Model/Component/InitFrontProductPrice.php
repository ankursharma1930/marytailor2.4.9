<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component;

class InitFrontProductPrice implements \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface
{
    /**
     * @var \Magento\CatalogRule\Observer\ProcessFrontFinalPriceObserver
     */
    private $getFrontFinalPrice;

    /**
     * @var \Magento\Framework\EventFactory
     */
    private $eventFactory;

    /**
     * @var \Magento\Framework\Event\ObserverFactory
     */
    private $observerFactory;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    private $catalogData;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * InitFrontProductPrice constructor.
     *
     * @param \Magento\CatalogRule\Observer\ProcessFrontFinalPriceObserver $getFrontFinalPrice
     * @param \Magento\Framework\EventFactory                              $eventFactory
     * @param \Magento\Framework\Event\ObserverFactory                     $observerFactory
     * @param \Magento\Catalog\Helper\Data                                 $catalogData
     * @param \Magento\Framework\App\State                                 $appState
     * @param \Psr\Log\LoggerInterface                                     $logger
     */
    public function __construct(
        \Magento\CatalogRule\Observer\ProcessFrontFinalPriceObserver $getFrontFinalPrice,
        \Magento\Framework\EventFactory $eventFactory,
        \Magento\Framework\Event\ObserverFactory $observerFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\App\State $appState,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->getFrontFinalPrice = $getFrontFinalPrice;
        $this->eventFactory = $eventFactory;
        $this->observerFactory = $observerFactory;
        $this->catalogData = $catalogData;
        $this->appState = $appState;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface                    $componentDataLocator
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function execute(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator
    ) : \Magento\Catalog\Api\Data\ProductInterface {
        if (! $product->getPramFrontFinalPriceInited()) {
            $product->setCustomerGroupId($componentDataLocator->getCustomerGroupId());

            $this->applyCartPriceRules($product);

            $priceWithTax = $this->catalogData->getTaxPrice($product, $product->getData('final_price'));
            $product->setFinalPrice($priceWithTax);

            $product->setPriceCalculation(false);
            $product->setPramFrontFinalPriceInited(true);
        }

        return $product;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     */
    private function applyCartPriceRules(\Magento\Catalog\Api\Data\ProductInterface $product)
    {
        try {
            if ($this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_CRONTAB) {
                $event = $this->eventFactory->create();
                $event->setName('pramp_pseudo_catalog_product_get_final_price');
                $event->setProduct($product);

                $wrapper = $this->observerFactory->create(\Magento\Framework\Event\Observer::class);
                $wrapper->setData(['event' => $event]);

                $this->getFrontFinalPrice->execute($wrapper);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
