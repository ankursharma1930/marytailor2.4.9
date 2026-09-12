<?php
/**
 * @package     Plumrocket_AmpEmailApi
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmailApi\Model;

/**
 * Interface LocatorInterface
 *
 * @since 1.0.0
 */
interface LocatorInterface
{

    /**
     * Reset locator data.
     *
     * @return LocatorInterface
     */
    public function resetData(): LocatorInterface;
}
