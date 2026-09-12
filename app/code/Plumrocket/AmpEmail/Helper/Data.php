<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface;

/**
 * Class Data
 *
 * @deprecated since 2.2.0
 */
class Data extends AbstractHelper
{
    /**
     * Section name for configs
     */
    const SECTION_ID = 'prampemail';

    const AMP_CMS_SOCIAL_BLOCK_IDENTIFIER = 'pr_amp_email_social_buttons';

    /**
     * @param bool $withEmptyLine
     * @return array
     * @deprecated since 2.2.0
     * @see \Plumrocket\AmpEmail\Model\OptionSource\TemplateStatus::toOptionHash
     */
    public function getAmpEmailTemplateStatuses(bool $withEmptyLine = false) : array
    {
        $statuses = [
            AmpTemplateInterface::AMP_EMAIL_STATUS_DISABLED => 'Disabled',
            AmpTemplateInterface::AMP_EMAIL_STATUS_LIVE => 'Enabled - Live',
            AmpTemplateInterface::AMP_EMAIL_STATUS_SANDBOX => 'Enabled - Sandbox',
        ];

        if ($withEmptyLine) {
            $statuses = [null => null] + $statuses;
        }

        return $statuses;
    }
}
