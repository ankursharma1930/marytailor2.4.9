<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\ViewModel\Sales\Order\Email\Items;

use Magento\Catalog\Helper\Image;

class DefaultItems implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \Magento\Framework\View\ConfigInterface
     */
    private $viewConfig;

    /**
     * @var \Magento\Framework\Config\View
     */
    private $configView;

    /**
     * TODO: replace with ImageFactory after left support magento v2.2
     *
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    private $imageBuilder;

    /**
     * DefaultItems constructor.
     *
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\Framework\View\ConfigInterface     $configView
     */
    public function __construct(
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\View\ConfigInterface $configView
    ) {
        $this->imageBuilder = $imageBuilder;
        $this->viewConfig = $configView;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getProductImage(\Magento\Sales\Model\Order\Item $orderItem) : \Magento\Catalog\Block\Product\Image
    {
        $this->imageBuilder->setImageId('cart_page_product_thumbnail');
        $this->imageBuilder->setProduct($orderItem->getProduct());
        return $this->imageBuilder->create();
    }

    /**
     * Returns image attribute
     *
     * @param string $imageId
     * @param string $attributeName
     * @param string $default
     * @return string|int
     */
    public function getImageAttribute($imageId, $attributeName, $default = null)
    {
        $attributes = $this->getConfigView()
                           ->getMediaAttributes('Magento_Catalog', Image::MEDIA_TYPE_CONFIG_NODE, $imageId);
        return $attributes[$attributeName] ?? $default;
    }

    /**
     * Retrieve config view
     *
     * @return \Magento\Framework\Config\View
     */
    private function getConfigView() : \Magento\Framework\Config\View
    {
        if (!$this->configView) {
            $this->configView = $this->viewConfig->getViewConfig();
        }
        return $this->configView;
    }
}
