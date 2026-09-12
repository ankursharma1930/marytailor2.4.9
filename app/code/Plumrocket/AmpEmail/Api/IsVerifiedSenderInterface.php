<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface IsVerifiedSenderInterface
{
    /**
     * @param string $email
     * @param bool   $allowRequestFromAmpPlayground
     * @return bool
     */
    public function execute(string $email, bool $allowRequestFromAmpPlayground = false) : bool;
}
