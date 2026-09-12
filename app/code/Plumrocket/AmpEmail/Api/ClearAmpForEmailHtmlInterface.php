<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface ClearAmpForEmailHtmlInterface
{
    /**
     * Replace disallowed html on amp for email alternative
     *
     * @param string $html
     * @return string
     */
    public function execute(string $html) : string;
}
