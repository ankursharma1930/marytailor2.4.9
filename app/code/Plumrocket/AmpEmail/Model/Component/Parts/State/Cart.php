<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\Parts\State;

use Plumrocket\AmpEmail\Model\Component\Parts\StateCompositeRenderStrategy;

class Cart implements \Plumrocket\AmpEmail\Model\ComponentPartRenderStrategyInterface
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * Cart constructor.
     *
     * @param \Magento\Framework\View\LayoutInterface $layout
     */
    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->layout = $layout;
    }

    /**
     * @param array  $partContents
     * @param string $emailContent
     * @return string
     */
    public function render(array $partContents, string $emailContent) : string
    {
        $idPrefix = 'p';

        $blockData = [
            'template' => 'component/v1/state/cart.phtml',
            'state_id' => 'cart',
            'id_prefix' => $idPrefix,
        ];

        $cartStateBlock = $this->layout->createBlock(
            \Plumrocket\AmpEmail\Block\Component\State\Cart::class,
            null,
            ['data' => $blockData]
        );

        return str_replace(
            StateCompositeRenderStrategy::STATE_PART_PLACEHOLDER,
            StateCompositeRenderStrategy::STATE_PART_PLACEHOLDER . "\n" .$cartStateBlock->toHtml(),
            $emailContent
        );
    }
}
