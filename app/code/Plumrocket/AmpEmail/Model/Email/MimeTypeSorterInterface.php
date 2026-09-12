<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email;

interface MimeTypeSorterInterface
{
    /**
     * Sort email message parts by mime types
     *
     * @param \Laminas\Mime\Part[] $parts
     * @return \Laminas\Mime\Part[]
     */
    public function sort(array $parts) : array;
}
