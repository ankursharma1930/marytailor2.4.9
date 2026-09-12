<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2023 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\OptionSource;

use Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface;
use Plumrocket\Base\Model\OptionSource\AbstractSource;

/**
 * @since 2.2.0
 */
class TemplateStatus extends AbstractSource
{

    /**
     * @inheritdoc
     */
    public function toOptionHash(): array
    {
        return [
            AmpTemplateInterface::AMP_EMAIL_STATUS_DISABLED => 'Disabled',
            AmpTemplateInterface::AMP_EMAIL_STATUS_LIVE => 'Enabled - Live',
            AmpTemplateInterface::AMP_EMAIL_STATUS_SANDBOX => 'Enabled - Sandbox',
        ];
    }
}
