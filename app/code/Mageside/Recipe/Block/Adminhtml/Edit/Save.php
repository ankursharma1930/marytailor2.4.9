<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Adminhtml\Edit;

use Magento\Ui\Component\Control\Container;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;

class Save extends Generic implements ButtonProviderInterface
{

    public function getButtonData()
    {

        return [
            'label' => __('Save'),
            'class' => 'save form',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'recipe_form.recipe_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    $this->getCurrentStore()
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'class_name' => Container::SPLIT_BUTTON,
            'options' => $this->getOptions(),
        ];
    }

    protected function getOptions()
    {
        $options[] = [
            'id_hard' => 'save',
            'label' => __('Save'),
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'recipe_form.recipe_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    $this->getCurrentStore(true)
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $options[] = [
            'id_hard' => 'save_and_continue',
            'label' => __('Save And Continue'),
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'recipe_form.recipe_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    $this->getCurrentStore(true)
                                ]
                            ],
                        ]
                    ]
                ]
            ],
        ];

        return $options;
    }

    public function getCurrentStore($back = false)
    {
        $storeId = $this->context->getRequestParam('store');
        if ($storeId && $back) {
            return [
                'store' => $storeId,
                'back' => 'continue'
            ];
        } elseif ($storeId && !$back) {
            return [
                'store' => $storeId
            ];
        }
        return [];
    }
}
