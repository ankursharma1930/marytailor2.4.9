<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Testing;

use Magento\Customer\Api\Data\CustomerInterface;

class CustomerDataGenerator
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
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var \Magento\Customer\Helper\View
     */
    private $customerViewHelper;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var \Plumrocket\AmpEmail\Model\Testing\Sales\GetOrderVariables
     */
    private $getOrderVariables;

    /**
     * CustomerDataGenerator constructor.
     *
     * @param \Magento\Store\Model\App\Emulation                         $appEmulation
     * @param \Magento\Sales\Api\OrderRepositoryInterface                $orderRepository
     * @param \Magento\Newsletter\Model\SubscriberFactory                $subscriberFactory
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
     * @param \Magento\Customer\Model\CustomerRegistry                   $customerRegistry
     * @param \Magento\Customer\Helper\View                              $customerViewHelper
     * @param \Magento\Framework\Reflection\DataObjectProcessor          $dataObjectProcessor
     * @param \Plumrocket\AmpEmail\Model\Testing\Sales\GetOrderVariables $getOrderVariables
     */
    public function __construct(
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Customer\Helper\View $customerViewHelper,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Plumrocket\AmpEmail\Model\Testing\Sales\GetOrderVariables $getOrderVariables
    ) {
        $this->appEmulation = $appEmulation;
        $this->orderRepository = $orderRepository;
        $this->subscriberFactory = $subscriberFactory;
        $this->storeManager = $storeManager;
        $this->customerRegistry = $customerRegistry;
        $this->customerViewHelper = $customerViewHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->getOrderVariables = $getOrderVariables;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param array                                        $data
     * @param string                                       $area
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate(CustomerInterface $customer, array $data, string $area) : array
    {
        // Welcome
        $mergedCustomerData = $this->customerRegistry->retrieveSecureData($customer->getId());
        $customerData = $this->dataObjectProcessor
            ->buildOutputDataArray($customer, \Magento\Customer\Api\Data\CustomerInterface::class);
        $mergedCustomerData->addData($customerData);
        $mergedCustomerData->setData('name', $this->customerViewHelper->getCustomerName($customer));

        $result = ['customer' => $mergedCustomerData];
        $result['customer_id'] = $mergedCustomerData->getId();
        $result['customerName'] = $mergedCustomerData->getName();
        $result['back_url'] = ''; // Used for registration email

        // Order
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

        // Base info
        $result['store_id'] = $storeId = $result['order']->getStoreId();
        $result['store'] = $this->storeManager->getStore($storeId);

        if (\Magento\Framework\App\Area::AREA_FRONTEND === $area) {
            $this->appEmulation->startEnvironmentEmulation($storeId, $area, true);
        }

        // Subscriber
        /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
        $subscriber = $this->subscriberFactory->create();
        $subscriber->loadByEmail($customer->getEmail());
        if ($subscriber->getId()) {
            $result['subscriber'] = $subscriber;
        }

        $productIds = [];
        $initialAlertPrices = [];
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($result['order']->getAllVisibleItems() as $orderItem) {
            $productId = (int) $orderItem->getProductId();

            $productIds[$productId] = $productId;
            $initialAlertPrices[$productId] = [
                'id' => $productId,
                'price' => $orderItem->getPrice() + $orderItem->getPrice() * 0.11, // 11%, why not?
            ];
        }

        $result['orderProductIds'] = array_values($productIds);
        $result['initialAlertPrices'] = array_values($initialAlertPrices);

        if (\Magento\Framework\App\Area::AREA_FRONTEND === $area) {
            $this->appEmulation->stopEnvironmentEmulation();
        }

        return $result;
    }
}
