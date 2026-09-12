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

namespace Plumrocket\AmpEmailApi\Block;

use Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface;

trait ComponentTrait
{
    /**
     * @var ComponentPartsCollectorInterface|null
     */
    private $prampComponentPartsCollector;

    /**
     * @var array|null
     */
    private $prampEmailTemplateVars;

    /**
     * @param \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface $componentPartsCollector
     * @return \Plumrocket\AmpEmailApi\Api\ComponentInterface
     */
    public function setComponentPartsCollector(
        ComponentPartsCollectorInterface $componentPartsCollector
    ) : \Plumrocket\AmpEmailApi\Api\ComponentInterface {
        $this->prampComponentPartsCollector = $componentPartsCollector;
        return $this;
    }

    /**
     * @return \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface
     */
    public function getComponentPartsCollector() : ComponentPartsCollectorInterface
    {
        return $this->prampComponentPartsCollector;
    }

    /**
     * @param array $templateVars
     * @return \Plumrocket\AmpEmailApi\Api\ComponentInterface
     */
    public function setEmailTemplateVars(array $templateVars) : \Plumrocket\AmpEmailApi\Api\ComponentInterface
    {
        $this->prampEmailTemplateVars = $templateVars;
        return $this;
    }

    /**
     * @param null $key
     * @return array|null|mixed
     */
    public function getEmailTemplateVars($key = null)
    {
        if ($key) {
            return $this->prampEmailTemplateVars[$key] ?? null;
        }

        return $this->prampEmailTemplateVars;
    }
}
