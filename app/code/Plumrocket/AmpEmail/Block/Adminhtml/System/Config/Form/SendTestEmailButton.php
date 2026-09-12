<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Adminhtml\System\Config\Form;

/**
 * Class SendTestEmailButton
 * @since 1.0.1
 */
class SendTestEmailButton extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element) : string
    {
        return $this->getButtonHtml($element);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    private function getButtonHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element) : string
    {
        /** @var \Magento\Backend\Block\Widget\Button $button */
        try {
            $button = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Button::class
            );
            $onClickAction = sprintf(
                'javascript:window.sendPrAmpTestEmail("%s");',
                $this->getUrl('prampemail/test_email/send')
            );
            $button->setData(
                [
                    'id' => $element->getHtmlId(),
                    'label' => __('Send'),
                    'class' => 'pramp_send_test_email',
                    'onclick' => $onClickAction,
                ]
            );
            $html = $button->toHtml();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $html = '';
        }

        return $html;
    }

    /**
     * Remove scope label from button
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element) : string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }
}
