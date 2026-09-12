<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface ComponentConfigResolverInterface
{
    /**
     * Retrieve config for specific component
     *
     * @param string $type
     * @return array
     */
    public function execute(string $type) : array;
}
