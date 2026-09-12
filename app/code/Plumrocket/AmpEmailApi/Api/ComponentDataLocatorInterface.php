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

interface ComponentDataLocatorInterface extends \Plumrocket\AmpEmailApi\Model\LocatorInterface
{
    /**
     * @return int
     */
    public function getCustomerId() : int;

    /**
     * @param int $customerId
     * @return \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    public function setCustomerId(int $customerId) : ComponentDataLocatorInterface;

    /**
     * @return int
     */
    public function getCustomerGroupId() : int;

    /**
     * @param int $customerGroupId
     * @return \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    public function setCustomerGroupId(int $customerGroupId) : ComponentDataLocatorInterface;

    /**
     * @return int
     */
    public function getStoreId() : int;

    /**
     * @param int $storeId
     * @return \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    public function setStoreId(int $storeId) : ComponentDataLocatorInterface;

    /**
     * @return ComponentDataLocatorInterface
     */
    public function resetData() : \Plumrocket\AmpEmailApi\Model\LocatorInterface;

    /**
     * @param string $email
     * @return \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    public function setRecipientEmail(string $email) : ComponentDataLocatorInterface;

    /**
     * @return string
     */
    public function getRecipientEmail() : string;

    /**
     * @param string $tokenHash
     * @return \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    public function setToken(string $tokenHash) : ComponentDataLocatorInterface;

    /**
     * @return string
     */
    public function getToken() : string;

    /**
     * @param bool $flag
     * @return \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    public function setIsManualTestingMode(bool $flag) : ComponentDataLocatorInterface;

    /**
     * @return bool
     */
    public function isManualTestingMode() : bool;
}
