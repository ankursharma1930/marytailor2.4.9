<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Testing;

class GuestDataGenerator
{
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Plumrocket\AmpEmail\Model\Testing\Sales\GetOrderVariables
     */
    private $getOrderVariables;

    /**
     * GuestDataGenerator constructor.
     *
     * @param \Magento\Store\Model\App\Emulation                         $appEmulation
     * @param \Magento\Sales\Api\OrderRepositoryInterface                $orderRepository
     * @param \Magento\Newsletter\Model\SubscriberFactory                $subscriberFactory
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
     * @param \Plumrocket\AmpEmail\Model\Testing\Sales\GetOrderVariables $getOrderVariables
     */
    public function __construct(
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Testing\Sales\GetOrderVariables $getOrderVariables
    ) {
        $this->appEmulation = $appEmulation;
        $this->orderRepository = $orderRepository;
        $this->subscriberFactory = $subscriberFactory;
        $this->storeManager = $storeManager;
        $this->getOrderVariables = $getOrderVariables;
    }

    /**
     * @param string $email
     * @param array  $data
     * @param string $area
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generate(string $email, array $data, string $area) : array
    {
        $result = [];

        try {
            $order = $this->orderRepository->get((int) $data['customer_order']);
            $orderData = $this->getOrderVariables->execute($order);
            $result = array_merge($result, $orderData);
        } catch (\Magento\Framework\Exception\InputException $inputException) { //@codingStandardsIgnoreLine
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Order repository: %1', $inputException->getMessage())
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $noSuchEntityException) { //@codingStandardsIgnoreLine
            throw new \Magento\Framework\Exception\LocalizedException(__($noSuchEntityException->getMessage()));
        }

        $result['store_id'] = $storeId = $result['order']->getStoreId();
        $result['store'] = $this->storeManager->getStore($storeId);

        if (\Magento\Framework\App\Area::AREA_FRONTEND === $area) {
            $this->appEmulation->startEnvironmentEmulation($storeId, $area, true);
        }

        /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
        $subscriber = $this->subscriberFactory->create();
        $subscriber->loadByEmail($email);
        if ($subscriber->getId()) {
            $result['subscriber'] = $subscriber;
        }

        $productIds = [];
        foreach ($result['order']->getItems() as $orderItem) {
            $productIds[] = (int) $orderItem->getProductId();
        }
        $result['orderProductIds'] = array_unique($productIds);
        $result['initialAlertPrices'] = []; // Guest cannot create price alert

        if (\Magento\Framework\App\Area::AREA_FRONTEND === $area) {
            $this->appEmulation->stopEnvironmentEmulation();
        }

        return $result;
    }
}
