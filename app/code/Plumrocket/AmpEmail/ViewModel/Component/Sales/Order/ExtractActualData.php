<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\ViewModel\Component\Sales\Order;

/**
 * Extract data for component "Order Actual Information"
 *
 * @since 1.0.0
 */
class ExtractActualData
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Plumrocket\AmpEmail\Model\Shipping\CreateTrackUrl
     */
    private $createTrackUrl;

    /**
     * ExtractActualData constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository
     * @param \Plumrocket\AmpEmail\Model\Shipping\CreateTrackUrl $createTrackUrl
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Plumrocket\AmpEmail\Model\Shipping\CreateTrackUrl $createTrackUrl
    ) {
        $this->orderRepository = $orderRepository;
        $this->createTrackUrl = $createTrackUrl;
    }

    /**
     * @param int $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(int $orderId)
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);

        $tackingNumbers = [];
        foreach ($order->getTracksCollection()->getItems() as $shipmentTrack) {
            $tackingNumbers[] = [
                'url' => $this->createTrackUrl->execute($shipmentTrack),
                'number' => $shipmentTrack->getTrackNumber(),
            ];
        }

        return [
            'orderStatusLabel' => $order->getStatusLabel(),
            'hasTrackingNumbers' => (bool) $tackingNumbers,
            'trackingNumbers' => $tackingNumbers,
        ];
    }
}
