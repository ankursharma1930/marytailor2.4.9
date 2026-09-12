<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Setup\Patch\Data;

use Magento\Cms\Model\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Plumrocket\AmpEmail\Helper\Data;

/**
 * @since 2.1.0
 */
class AddSocialButtonsBlock implements DataPatchInterface
{
    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    private $blockFactory;

    /**
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param \Magento\Framework\App\State    $state
     */
    public function __construct(
        BlockFactory $blockFactory,
        State $state
    ) {
        $this->blockFactory = $blockFactory;

        try {
            $state->setAreaCode('adminhtml');
        } catch (\Exception $e) {// phpcs:ignore
        }
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        /**
         * Set block identifier and check it for storeID = 0
         *
         * @var $block \Magento\Cms\Model\Block
         */
        $block = $this->blockFactory->create();
        $blockIdentifier = 'pr_amp_email_social_buttons';
        $block->setStoreId(0)->load($blockIdentifier);

        if (! $block->getId()) {
            /**
             * Prepare data for AMP footer block and create it
             */
            $blockContent = '<ul class="social">
    <li class="social__item">
        <a href="https://www.facebook.com/" aria-label="Facebook">
            <img src="{{view url=\'Plumrocket_AmpEmail/images/social/facebook_icon.png\'}}" width="33" height="33" alt="Facebook"/>
        </a>
    </li>
    <li class="social__item">
        <a href="https://twitter.com"  aria-label="Twitter">
            <img src="{{view url=\'Plumrocket_AmpEmail/images/social/twitter_icon.png\'}}" width="33" height="33" alt="Twitter"/>
        </a>
    </li>
    <li class="social__item">
        <a href="https://www.youtube.com/" aria-label="Youtube">
            <img src="{{view url=\'Plumrocket_AmpEmail/images/social/youtube_icon.png\'}}" width="33" height="33" alt="Youtube"/>
        </a>
    </li>
    <li class="social__item">
        <a href="https://www.instagram.com" aria-label="Instagram">
            <img src="{{view url=\'Plumrocket_AmpEmail/images/social/instagram_icon.png\'}}" width="33" height="33" alt="Instagram"/>
        </a>
    </li>
</ul>';

            $socialBlockData = [
                Block::IDENTIFIER => $blockIdentifier,
                Block::TITLE => 'Amp Email Social Buttons',
                Block::CONTENT => $blockContent,
                Block::IS_ACTIVE => true,
                'page_layout' => '1column',
                'stores' => [0],
            ];

            $block->setData($socialBlockData)->save();
        }
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
