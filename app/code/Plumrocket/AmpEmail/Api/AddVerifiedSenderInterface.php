<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface AddVerifiedSenderInterface
{
    /**
     * @param array $emails
     * @return bool
     */
    public function execute(array $emails) : bool;
}
