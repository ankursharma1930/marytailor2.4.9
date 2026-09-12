<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Observer;

class AddVerifiedSenderObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Plumrocket\AmpEmail\Api\AddVerifiedSenderInterface
     */
    private $addVerifiedSender;

    /**
     * AddVerifiedSenderObserver constructor.
     *
     * @param \Plumrocket\AmpEmail\Api\AddVerifiedSenderInterface $addVerifiedSender
     */
    public function __construct(\Plumrocket\AmpEmail\Api\AddVerifiedSenderInterface $addVerifiedSender)
    {
        $this->addVerifiedSender = $addVerifiedSender;
    }

    /**
     * Don't need check if module enabled
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Plumrocket\AmpEmail\Model\Email\AmpMessageInterface $message */
        $message = $observer->getMessage();
        $this->addVerifiedSender->execute($message->getSenders());
    }
}
