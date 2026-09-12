<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

use Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface;

/**
 * Class AmpTemplateProvider
 *
 * Load and cache amp templates
 */
interface AmpTemplateProviderInterface
{
    /**
     * @param string|int $templateId
     * @param bool       $forceReload
     * @return \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTemplate($templateId, bool $forceReload = false) : AmpTemplateInterface;

    /**
     * Check if exist AMP content for specific template
     *
     * @param $templateId
     * @return bool
     */
    public function isExistAmpForEmailById($templateId) : bool;
}
