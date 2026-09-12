<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface GetVerifiedSenderListInterface
{
    /**
     * @param bool $reload
     * @return string[]
     */
    public function execute(bool $reload = false) : array;
}
