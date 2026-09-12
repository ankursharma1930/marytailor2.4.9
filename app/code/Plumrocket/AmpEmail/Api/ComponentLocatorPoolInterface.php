<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Api;

interface ComponentLocatorPoolInterface
{
    /**
     * ComponentLocatorPoolInterface constructor.
     *
     * @param array $locators
     */
    public function __construct(array $locators = []);

    /**
     * @return \Plumrocket\AmpEmailApi\Model\LocatorInterface[]
     */
    public function getList() : array;
}
