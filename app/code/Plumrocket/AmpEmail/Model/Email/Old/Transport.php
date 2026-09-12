<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email\Old;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\ScopeInterface;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Sendmail;

/**
 * Class Transport
 * @since 1.1.0
 * @deprecated use only for backward compatibility
 */
class Transport implements TransportInterface
{
    /**
     * Whether return path should be set or no.
     *
     * Possible values are:
     * 0 - no
     * 1 - yes (set value as FROM address)
     * 2 - use custom value
     *
     * @var int
     */
    private $isSetReturnPath;

    /**
     * @var string|null
     */
    private $returnPathValue;

    /**
     * @var Sendmail
     */
    private $laminasTransport;

    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * Transport constructor.
     *
     * @param                                                    $message
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param null                                               $parameters
     */
    public function __construct(
        $message,
        ScopeConfigInterface $scopeConfig,
        $parameters = null
    ) {
        $this->isSetReturnPath = (int) $scopeConfig->getValue(
            \Magento\Email\Model\Transport::XML_PATH_SENDING_SET_RETURN_PATH,
            ScopeInterface::SCOPE_STORE
        );
        $this->returnPathValue = $scopeConfig->getValue(
            \Magento\Email\Model\Transport::XML_PATH_SENDING_RETURN_PATH_EMAIL,
            ScopeInterface::SCOPE_STORE
        );

        $this->laminasTransport = new Sendmail($parameters);
        $this->message = $message;
    }

    /**
     * @inheritdoc
     */
    public function sendMessage()
    {
        try {
            $laminasMessage = Message::fromString($this->message->getRawMessage())->setEncoding('utf-8');
            if (2 === $this->isSetReturnPath && $this->returnPathValue) {
                $laminasMessage->setSender($this->returnPathValue);
            } elseif (1 === $this->isSetReturnPath && $laminasMessage->getFrom()->count()) {
                $fromAddressList = $laminasMessage->getFrom();
                $fromAddressList->rewind();
                $laminasMessage->setSender($fromAddressList->current()->getEmail());
            }

            $this->laminasTransport->send($laminasMessage);
        } catch (\Exception $e) {
            throw new MailException(new Phrase($e->getMessage()), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }
}
