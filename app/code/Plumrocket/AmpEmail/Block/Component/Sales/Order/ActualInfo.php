<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Component\Sales\Order;

/**
 * @since 1.0.0
 */
class ActualInfo extends \Plumrocket\AmpEmailApi\Block\AbstractComponent
{
    /**
     * @var array|null
     */
    private $orderActualData;

    /**
     * @var \Plumrocket\AmpEmail\ViewModel\Component\Sales\Order\GetOrder
     */
    private $getOrder;

    /**
     * @var \Plumrocket\AmpEmail\ViewModel\Component\Sales\Order\ExtractActualData
     */
    private $extractActualData;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * ActualInfo constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context                       $context
     * @param \Magento\Framework\Url                                                 $frontUrlBuilder
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface              $componentDataLocator
     * @param \Magento\Framework\View\Asset\Repository                               $viewAssetRepository
     * @param \Plumrocket\AmpEmail\ViewModel\Component\Sales\Order\GetOrder          $getOrder
     * @param \Plumrocket\AmpEmail\ViewModel\Component\Sales\Order\ExtractActualData $extractActualData
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface                   $localeDate
     * @param array                                                                  $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url $frontUrlBuilder,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Magento\Framework\View\Asset\Repository $viewAssetRepository,
        \Plumrocket\AmpEmail\ViewModel\Component\Sales\Order\GetOrder $getOrder,
        \Plumrocket\AmpEmail\ViewModel\Component\Sales\Order\ExtractActualData $extractActualData,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        array $data = []
    ) {
        parent::__construct($context, $frontUrlBuilder, $componentDataLocator, $viewAssetRepository, $data);
        $this->getOrder = $getOrder;
        $this->extractActualData = $extractActualData;
        $this->localeDate = $localeDate;
    }

    /**
     * @return bool|\Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getOrder->execute($this);
    }

    /**
     * @return string
     */
    protected function _toHtml(): string
    {
        if (! $this->getOrder()) {
            $this->_logger->info('AmpForEmail::not found order for component ' . get_class($this));
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return array
     */
    public function getOrderActualData(): array
    {
        if (null === $this->orderActualData) {
            try {
                $this->orderActualData = $this->extractActualData->execute((int) $this->getOrder()->getId());
            } catch (\Magento\Framework\Exception\InputException $e) {
                $this->_logger->critical($e);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->_logger->critical($e);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_logger->critical($e);
            }

            $this->orderActualData = [];
        }

        return $this->orderActualData;
    }

    /**
     * @return string
     */
    public function getOrderStatusLabel(): string
    {
        return $this->getOrderActualData()['orderStatusLabel'] ?? '';
    }

    /**
     * @return array
     */
    public function getOrderTrackingNumbers(): array
    {
        return $this->getOrderActualData()['trackingNumbers'] ?? [];
    }

    /**
     * @return bool
     */
    public function hasOrderTrackingNumbers(): bool
    {
        return $this->getOrderActualData()['hasTrackingNumbers'] ?? false;
    }

    /**
     * @param int $orderId
     * @return string
     */
    public function getActualInfoUrl(int $orderId): string
    {
        return $this->getAmpApiUrl('amp-email-api/V1/sales_order_actual_info', ['order' => $orderId]);
    }

    /**
     * @return string
     */
    public function getCurrentDate(): string
    {
        return $this->localeDate->formatDateTime(
            'now',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL
        );
    }

    /**
     * @return string
     */
    public function getOrderViewUrl(): string
    {
        return $this->getFrontUrl('sales/order/view', ['order_id' => $this->getOrder()->getId()]);
    }
}
