<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_AmpEmailApi
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmailApi\Api;

interface ComponentInterface extends \Magento\Widget\Block\BlockInterface
{
    /**
     * Retrieve css file for component, can be empty string
     *
     * @return string
     */
    public function getStyleFileId() : string;

    /**
     * Retrieve version of component
     *
     * @return int
     */
    public function getVersion() : int;

    /**
     * @param \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface $componentPartsCollector
     * @return \Plumrocket\AmpEmailApi\Api\ComponentInterface
     */
    public function setComponentPartsCollector(
        \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface $componentPartsCollector
    ) : \Plumrocket\AmpEmailApi\Api\ComponentInterface;

    /**
     * @param array $templateVars
     * @return \Plumrocket\AmpEmailApi\Api\ComponentInterface
     */
    public function setEmailTemplateVars(array $templateVars) : \Plumrocket\AmpEmailApi\Api\ComponentInterface;
}
