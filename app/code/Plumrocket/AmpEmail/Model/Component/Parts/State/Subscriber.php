<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\Parts\State;

use Plumrocket\AmpEmail\Model\Component\Parts\StateCompositeRenderStrategy;

class Subscriber implements \Plumrocket\AmpEmail\Model\ComponentPartRenderStrategyInterface
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * Subscriber constructor.
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
        $blockData = [
            'template' => 'Plumrocket_AmpEmail::component/v1/state/subscriber.phtml',
            'state_id' => 'subscriber',
        ];

        $subscriberStateBlock = $this->layout->createBlock(
            \Magento\Framework\View\Element\Template::class,
            null,
            ['data' => $blockData]
        );

        return str_replace(
            StateCompositeRenderStrategy::STATE_PART_PLACEHOLDER,
            StateCompositeRenderStrategy::STATE_PART_PLACEHOLDER . "\n" . $subscriberStateBlock->toHtml(),
            $emailContent
        );
    }
}
