<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model;

/**
 * Interface AddAmpAlternativesToTemplatesInterface
 *
 * @api
 * @since 1.0.1
 */
interface AddAmpAlternativesToTemplatesInterface
{
    /**
     * Return count of changed email templates
     *
     * @return int
     */
    public function execute() : int;
}
