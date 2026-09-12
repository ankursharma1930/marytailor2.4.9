<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Component\Cms;

/**
 *
 * @method setBlockId(string $identifier)
 */
class Block extends \Plumrocket\AmpEmailApi\Block\AbstractComponent
{
    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    private $filterProvider;

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    private $blockFactory;

    /**
     * @var \Magento\Cms\Model\Block
     */
    private $block;

    /**
     * Storage for used widgets
     *
     * @var array
     */
    protected static $widgetUsageMap = [];

    /**
     * Block constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context          $context
     * @param \Magento\Framework\Url                                    $frontUrlBuilder
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator
     * @param \Magento\Framework\View\Asset\Repository                  $viewAssetRepository
     * @param \Magento\Cms\Model\Template\FilterProvider                $filterProvider
     * @param \Magento\Cms\Model\BlockFactory                           $blockFactory
     * @param array                                                     $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url $frontUrlBuilder,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Magento\Framework\View\Asset\Repository $viewAssetRepository,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        array $data = []
    ) {
        parent::__construct($context, $frontUrlBuilder, $componentDataLocator, $viewAssetRepository, $data);
        $this->filterProvider = $filterProvider;
        $this->blockFactory = $blockFactory;
    }

    /**
     * Prepare block text and determine whether block output enabled or not.
     *
     * Prevent blocks recursion if needed.
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
        $blockId = $this->getData('block_id');
        $blockHash = get_class($this) . $blockId;

        if (isset(self::$widgetUsageMap[$blockHash])) {
            return $this;
        }
        self::$widgetUsageMap[$blockHash] = true;

        $block = $this->getBlock();

        if ($block && $block->isActive()) {
            $storeId = $this->getComponentDataLocator()->getStoreId();
            $this->setText(
                $this->filterAmpImages(
                    $this->filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent())
                )
            );
        }
        unset(self::$widgetUsageMap[$blockHash]);
        return $this;
    }

    /**
     * Get identities of the Cms Block
     *
     * @return array
     */
    public function getIdentities() : array
    {
        $block = $this->getBlock();

        if ($block) {
            return $block->getIdentities();
        }

        return [];
    }

    /**
     * Get block
     *
     * @return \Magento\Cms\Model\Block|null
     */
    private function getBlock()
    {
        if ($this->block) {
            return $this->block;
        }

        $blockId = $this->getData('block_id');

        if ($blockId) {
            $storeId = $this->getComponentDataLocator()->getStoreId();
            /** @var \Magento\Cms\Model\Block $block */
            $block = $this->blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            $this->block = $block;
        }

        return $this->block;
    }

    /**
     * @param string $html
     * @return string
     */
    private function filterAmpImages(string $html) : string
    {
        return str_replace('<img', '<amp-img', $html);
    }
}
