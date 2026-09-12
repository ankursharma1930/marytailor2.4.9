<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_AmpEmailApi
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmailApi\Api;

interface ComponentPartsCollectorInterface
{
    /**
     * Add part to list
     *
     * @param string $type
     * @param mixed  $part
     * @param null   $key
     * @return \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface
     */
    public function addPartToList(string $type, $part, $key = null) : ComponentPartsCollectorInterface;

    /**
     * Get unique parts
     *
     * @return \Generator
     */
    public function getGroupedParts() : \Generator;

    /**
     * Retrieve count of unique parts
     *
     * @return int
     */
    public function getCount() : int;

    /**
     * Either replace placeholder on part or put part in specific place
     *
     * @param string $ampEmailContent
     * @return string
     */
    public function renderIntoEmailContent(string $ampEmailContent) : string;
}
