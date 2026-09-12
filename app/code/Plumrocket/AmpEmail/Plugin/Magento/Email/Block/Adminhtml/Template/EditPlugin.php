<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Plugin\Magento\Email\Block\Adminhtml\Template;

class EditPlugin
{
    /**
     * @param \Magento\Email\Block\Adminhtml\Template\Edit $form
     * @param                                              $alias
     * @param                                              $block
     * @param                                              $params
     * @return array
     */
    public function beforeAddChild( //@codingStandardsIgnoreLine
        \Magento\Email\Block\Adminhtml\Template\Edit $form,
        $alias,
        $block,
        $params = []
    ) {
        if ('form' === $alias
            && \Magento\Email\Block\Adminhtml\Template\Edit\Form::class === $block
        ) {
            $block = \Plumrocket\AmpEmail\Block\Adminhtml\Email\Template\Edit\Form::class;
        }

        return [$alias, $block, $params];
    }
}
