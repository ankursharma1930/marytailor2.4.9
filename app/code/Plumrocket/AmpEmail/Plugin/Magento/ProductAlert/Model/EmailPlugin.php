<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Plugin\Magento\ProductAlert\Model;

class EmailPlugin
{
    /**
     * @var \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface
     */
    private $componentProductLocator;

    /**
     * @var \Plumrocket\AmpEmail\Model\Component\ProductAlert\CurrentAlertsDataLocator
     */
    private $currentAlertsDataLocator;

    /**
     * @var \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface
     */
    private $componentPriceAlertLocator;

    /**
     * @var \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    private $componentDataLocator;

    /**
     * EmailPlugin constructor.
     *
     * @param \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface                  $componentProductLocator
     * @param \Plumrocket\AmpEmail\Model\Component\ProductAlert\CurrentAlertsDataLocator $currentAlertsDataLocator
     * @param \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface               $componentPriceAlertLocator
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface                  $componentDataLocator
     */
    public function __construct(
        \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface $componentProductLocator,
        \Plumrocket\AmpEmail\Model\Component\ProductAlert\CurrentAlertsDataLocator $currentAlertsDataLocator,
        \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface $componentPriceAlertLocator,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator
    ) {
        $this->componentProductLocator = $componentProductLocator;
        $this->currentAlertsDataLocator = $currentAlertsDataLocator;
        $this->componentPriceAlertLocator = $componentPriceAlertLocator;
        $this->componentDataLocator = $componentDataLocator;
    }

    /**
     * Save products for amp component
     *
     * @param \Magento\ProductAlert\Model\Email $subject
     */
    public function beforeSend(\Magento\ProductAlert\Model\Email $subject)
    {
        $this->initProducts($subject);
        $customerId = $this->initCustomer($subject);
        $this->initInitialPrice($subject, $customerId);
    }

    /**
     * @param \Magento\ProductAlert\Model\Email $subject
     */
    protected function initProducts(\Magento\ProductAlert\Model\Email $subject)
    {
        try {
            $getProductsMethod = new \ReflectionMethod($subject, 'getProducts');
            $getProductsMethod->setAccessible(true);
            $products = $getProductsMethod->invoke($subject);
        } catch (\ReflectionException $e) {
            $products = [];
        }

        $this->componentProductLocator->setProducts($products);
    }

    /**
     * @param \Magento\ProductAlert\Model\Email $subject
     * @return int
     */
    protected function initCustomer(\Magento\ProductAlert\Model\Email $subject) : int
    {
        try {
            $customerProperty = new \ReflectionProperty(get_class($subject), '_customer');
            $customerProperty->setAccessible(true);
            $customerId = (int) $customerProperty->getValue($subject)->getId();
        } catch (\ReflectionException $e) {
            $customerId = 0;
        }

        $this->componentDataLocator->setCustomerId($customerId);
        return $customerId;
    }

    /**
     * @param \Magento\ProductAlert\Model\Email $subject
     * @param int                               $customerId
     */
    protected function initInitialPrice(\Magento\ProductAlert\Model\Email $subject, int $customerId)
    {
        try {
            $websiteProperty = new \ReflectionProperty(get_class($subject), '_website');
            $websiteProperty->setAccessible(true);
            $websiteId = (int) $websiteProperty->getValue($subject)->getId();
        } catch (\ReflectionException $e) {
            $websiteId = 0;
        }

        $alertData = $this->currentAlertsDataLocator->getForCustomer($customerId, $websiteId);
        if ($alertData) {
            foreach ($alertData['products'] as $productData) {
                $this->componentPriceAlertLocator->setInitialPrice($productData['id'], $productData['price']);
            }
        }
    }
}
