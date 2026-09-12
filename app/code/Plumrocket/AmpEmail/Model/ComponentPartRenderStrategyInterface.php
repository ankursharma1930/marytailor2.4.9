<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model;

/**
 * Interface ComponentPartRenderStrategyInterface
 *
 * @since 1.0.0
 */
interface ComponentPartRenderStrategyInterface
{
    /**
     * Render part into email
     *
     * @param mixed  $partContents
     * @param string $emailContent
     * @return string
     */
    public function render(array $partContents, string $emailContent) : string;
}
