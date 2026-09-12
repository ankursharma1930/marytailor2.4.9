<?php

namespace Dolphin\Productfaq\Block\Adminhtml\Productfaq\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Ui\Component\Control\Container;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Block\Widget\Context;

class SaveButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Context
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        RequestInterface $request
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'dolphin_productfaq_productfaq_form.dolphin_productfaq_productfaq_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    [
                                        'save' => 'save',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'class_name' => Container::SPLIT_BUTTON,
            'options' => $this->getOptions(),
            'sort_order' => 90,
        ];
    }

    /**
     * Get Button Options
     *
     * @return array
     */
    protected function getOptions()
    {
        $options[] = [
            'id_hard' => 'save_and_send',
            'label' => __('Save & Send'),
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'dolphin_productfaq_productfaq_form.dolphin_productfaq_productfaq_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    [
                                        'send' => 'send',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $options;
    }
}
