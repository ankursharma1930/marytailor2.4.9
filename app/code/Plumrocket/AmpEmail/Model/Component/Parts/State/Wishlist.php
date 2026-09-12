<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\Parts\State;

use Plumrocket\AmpEmail\Model\Component\Parts\StateCompositeRenderStrategy;

class Wishlist implements \Plumrocket\AmpEmail\Model\ComponentPartRenderStrategyInterface
{
    /**
     * @var \Plumrocket\AmpEmail\Model\Component\WishlistProductsResolver
     */
    private $wishlistProductsResolver;

    /**
     * @var \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    private $componentDataLocator;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * Wishlist constructor.
     *
     * @param \Plumrocket\AmpEmail\Model\Component\WishlistProductsResolver $wishlistProductsResolver
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface     $componentDataLocator
     * @param \Magento\Framework\View\LayoutInterface                       $layout
     * @param \Magento\Framework\Serialize\SerializerInterface              $serializer
     */
    public function __construct(
        \Plumrocket\AmpEmail\Model\Component\WishlistProductsResolver $wishlistProductsResolver,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->wishlistProductsResolver = $wishlistProductsResolver;
        $this->componentDataLocator = $componentDataLocator;
        $this->layout = $layout;
        $this->serializer = $serializer;
    }

    /**
     * @param array  $partContents
     * @param string $emailContent
     * @return string
     */
    public function render(array $partContents, string $emailContent) : string
    {
        $productIds = $this->wishlistProductsResolver->execute($this->componentDataLocator->getCustomerId());

        $idPrefix = 'p';

        $blockData = [
            'template' => 'component/v1/state/wishlist.phtml',
            'state_id' => 'wishlist',
            'products_wishlist_status' => $this->convertToJsonObject($idPrefix, $productIds),
            'id_prefix' => $idPrefix,
        ];

        $wishlistStateBlock = $this->layout->createBlock(
            \Plumrocket\AmpEmail\Block\Component\State\Wishlist::class,
            null,
            ['data' => $blockData]
        );

        return str_replace(
            StateCompositeRenderStrategy::STATE_PART_PLACEHOLDER,
            StateCompositeRenderStrategy::STATE_PART_PLACEHOLDER . "\n" . $wishlistStateBlock->toHtml(),
            $emailContent
        );
    }

    /**
     * @param string $idPrefix
     * @param array  $productIds
     * @return string
     */
    private function convertToJsonObject(string $idPrefix, array $productIds) : string
    {
        if ($idPrefix && ! is_numeric($idPrefix) && $productIds) {
            $productIds = array_unique($productIds);
            $result = [];
            foreach ($productIds as $productId) {
                $result[$idPrefix . $productId] = 1;
            }

            return $this->serializer->serialize($result);
        }

        return '{}';
    }
}
