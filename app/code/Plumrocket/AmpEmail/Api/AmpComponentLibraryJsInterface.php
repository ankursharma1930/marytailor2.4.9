<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface AmpComponentLibraryJsInterface
{
    /**
     * Get unique parts
     *
     * @return array
     */
    public function getList() : array;

    /**
     * Detect which native amp component are using in current template
     *
     * @param string $ampEmailContent
     * @return array
     */
    public function detectUsedAmpComponents(string $ampEmailContent) : array;

    /**
     * Either replace placeholder on part or put part in specific place
     *
     * @param string $ampEmailContent
     * @param array  $libraryList
     * @return string
     */
    public function renderIntoEmailContent(string $ampEmailContent, array $libraryList) : string;

    /**
     * Generate html like
     * <script async custom-element="amp-list" src="https://cdn.ampproject.org/v0/amp-list-0.1.js"></script>
     *
     * @param string $type
     * @return string
     */
    public function generateLibraryIncludeHtml(string $type) : string;
}
