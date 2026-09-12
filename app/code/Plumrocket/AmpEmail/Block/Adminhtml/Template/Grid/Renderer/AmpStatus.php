<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Adminhtml\Template\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface;
use Plumrocket\AmpEmail\Model\OptionSource\TemplateStatus;

/**
 * @since 1.0.0
 */
class AmpStatus extends AbstractRenderer
{

    /**
     * @var \Plumrocket\AmpEmail\Model\OptionSource\TemplateStatus
     */
    private $templateStatus;

    /**
     * @param \Magento\Backend\Block\Context                         $context
     * @param \Plumrocket\AmpEmail\Model\OptionSource\TemplateStatus $templateStatus
     * @param array                                                  $data
     */
    public function __construct(
        Context $context,
        TemplateStatus $templateStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->templateStatus = $templateStatus;
    }

    /**
     * Render status.
     *
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Framework\Phrase|string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($row->getPrampEmailEnable() && $row->getPrampEmailContent()) {
            $status = $row->getPrampEmailMode();
        } else {
            $status = AmpTemplateInterface::AMP_EMAIL_STATUS_DISABLED;
        }

        $allStatuses = $this->templateStatus->toOptionHash();
        if (! isset($allStatuses[$status])) {
            return __('Unknown');
        }

        return $this->htmlWrap($allStatuses[$status], $status);
    }

    /**
     * Wrap text by HTML.
     *
     * @param string $statusTitle
     * @param string $statusValue
     * @return string
     */
    private function htmlWrap(string $statusTitle, string $statusValue) : string
    {
        if (AmpTemplateInterface::AMP_EMAIL_STATUS_DISABLED !== $statusValue) {
            return '<span style="color: green;">' . __($statusTitle) . '</span>';
        }

        return __($statusTitle)->render();
    }
}
