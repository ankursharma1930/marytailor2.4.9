<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Block\Adminhtml\Edit\Button;

use Magento\Ui\Component\Control\Container;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;

class Save extends Generic implements ButtonProviderInterface
{

    public function getButtonData()
    {

        return [
            'id_hard' => 'save',
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'recipe_filter_form.recipe_filter_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    $this->getCurrentStore()

                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function getCurrentStore()
    {
        $storeId = $this->context->getRequestParam('store');
        if ($storeId) {
            return ['store' => $storeId];
        }
        return [];
    }
}
