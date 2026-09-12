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

namespace Plumrocket\AmpEmailApi\Model\Result;

interface AmpJsonInterface extends \Magento\Framework\Controller\ResultInterface
{
    /**
     * @param string|\Magento\Framework\Phrase $message
     * @return $this
     */
    public function addErrorMessage($message);

    /**
     * @param string|\Magento\Framework\Phrase $message
     * @return $this
     */
    public function addSuccessMessage($message);

    /**
     * @param \Exception                       $exception
     * @param \Magento\Framework\Phrase|string $message
     * @return $this
     */
    public function addExceptionMessage(\Exception $exception, $message = null);

    /**
     * @param string $key
     * @param        $value
     * @return $this
     */
    public function addData(string $key, $value);

    /**
     * Format data as simple list item
     *
     * @param bool $flag
     * @return \Plumrocket\AmpEmailApi\Model\Result\AmpJsonInterface
     */
    public function setIsSingleListItem(bool $flag);
}
