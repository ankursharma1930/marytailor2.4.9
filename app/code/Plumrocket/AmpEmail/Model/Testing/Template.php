<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Testing;

/**
 * Class Template
 * Disable load methods
 *
 * @package Plumrocket\AmpEmail\Model\Testing
 */
class Template extends \Magento\Email\Model\Template
{
    /**
     * @param string $templateId
     * @return $this|\Magento\Email\Model\Template
     */
    public function loadDefault($templateId)
    {
        return $this;
    }

    /**
     * @param int  $modelId
     * @param null $field
     * @return $this|\Magento\Email\Model\Template
     */
    public function load($modelId, $field = null)
    {
        return $this;
    }
}
