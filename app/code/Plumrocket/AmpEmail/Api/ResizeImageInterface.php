<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface ResizeImageInterface
{
    /**
     * Resize and crop image for container width and height
     *
     * @param string $image
     * @param int    $containerWidth
     * @param int    $containerHeight
     * @param string $mediaFolder
     * @param string $additionalPath
     * @param bool   $needCrop
     * @return array|bool
     */
    public function execute(
        string $image,
        int $containerWidth,
        int $containerHeight,
        string $mediaFolder,
        string $additionalPath = '',
        bool $needCrop = true
    );
}
