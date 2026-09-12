<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email;

use Laminas\Mail\Message;
use Laminas\Mail\MessageFactory;

class AmpMessage extends \Magento\Framework\Mail\Message implements AmpMessageInterface
{
    const TYPE_AMP = 'text/x-amp-html';

    /**
     * @var string
     */
    private $charset;

    /**
     * @var \Laminas\Mail\Message
     */
    private $laminasMessage;

    /**
     * @var PartsCollectorInterface
     */
    private $partsCollector;

    /**
     * @var \Laminas\Mime\Message
     */
    private $mimeMessage;

    /**
     * Forward compatibility
     *
     * @var string
     */
    private $currentMimeType = \Laminas\Mime\Mime::TYPE_TEXT;

    /**
     * @var \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface
     */
    private $emailAddressParser;

    /**
     * @var string
     */
    private $recipients = '';

    /**
     * @var string
     */
    private $mainRecipient;

    /**
     * AmpMessage constructor.
     *
     * @param PartsCollectorInterface                                      $partsCollector
     * @param \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface $emailAddressParser
     * @param \Laminas\Mime\Message                                        $mimeMessage
     * @param string                                                       $charset
     */
    public function __construct(
        \Plumrocket\AmpEmail\Model\Email\PartsCollectorInterface $partsCollector,
        \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface $emailAddressParser,
        \Laminas\Mime\Message $mimeMessage,
        string $charset = 'utf-8'
    ) {
        parent::__construct($charset);
        $this->partsCollector = $partsCollector;
        $this->charset = $charset;
        $this->emailAddressParser = $emailAddressParser;
        $this->mimeMessage = $mimeMessage;
        $this->laminasMessage = MessageFactory::getInstance(['encoding' => $this->charset]); //@codingStandardsIgnoreLine
    }

    /**
     * @param string $mimeType
     * @param mixed  $content
     * @return AmpMessage
     */
    private function setPart(string $mimeType, $content) : self
    {
        $part = $this->partsCollector->createPart($mimeType, $content, $this->laminasMessage->getEncoding());
        $this->partsCollector->addPartToList($part);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        $this->joinParts();
        return $this->laminasMessage->getBody();
    }

    /**
     * Created for compatibility with Mageplaza_EmailAttachments
     *
     * @return $this
     */
    public function setPartsToBody()
    {
        return $this->joinParts();
    }

    /**
     * @return $this
     */
    private function joinParts() : self
    {
        $this->partsCollector->applyToMessage($this->mimeMessage);
        $this->laminasMessage->setBody($this->mimeMessage);

        return $this->prepareHeaders();
    }

    /**
     * @deprecated since 2.2.3
     * @return \Laminas\Mail\Message
     */
    public function getZendMessage() : Message
    {
        return $this->laminasMessage;
    }

    /**
     * @return \Laminas\Mime\Message
     */
    public function getMimeMessage() : \Laminas\Mime\Message
    {
        return $this->mimeMessage;
    }

    /**
     * TODO: need refactor after Gmail add support amp + attachment
     * @link https://docs.zendframework.com/zend-mail/message/attachments/
     *
     * @return $this
     */
    private function prepareHeaders() : self
    {
        if ($this->partsCollector->hasAmpForEmail() && $this->partsCollector->getCount() > 1) {
            $headers = $this->laminasMessage->getHeaders();

            if ($headers->has('content-type')) {
                $header = $headers->get('content-type');
                $header->setType('multipart/alternative');
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRawMessage()
    {
        return $this->joinParts()->laminasMessage->toString();
    }

    /**
     * @inheritdoc
     */
    public function setSubject($subject)
    {
        $this->laminasMessage->setSubject($subject);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSubject()
    {
        return $this->laminasMessage->getSubject();
    }

    /**
     * @inheritdoc
     *
     * @deprecated 102.0.1 This function is missing the from name. The
     * setFromAddress() function sets both from address and from name.
     * @see setFromAddress()
     */
    public function setFrom($fromAddress)
    {
        $this->setFromAddress($fromAddress);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setFromAddress($fromAddress, $fromName = null)
    {
        $this->laminasMessage->setFrom($fromAddress, $fromName);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addTo($toAddress)
    {
        // Save addresses for testing mode - auto
        $this->recipients .= $toAddress . ' ';
        $this->laminasMessage->addTo($toAddress);
        $this->mainRecipient = $toAddress;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMainRecipient()
    {
        return $this->mainRecipient;
    }

    /**
     * @return array
     */
    public function getSenders()
    {
        $emails = [];
        foreach ($this->laminasMessage->getFrom() as $email => $address) {
            $emails[] = $email;
        }

        return $emails;
    }

    /**
     * @inheritdoc
     */
    public function addCc($ccAddress)
    {
        $this->laminasMessage->addCc($ccAddress);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addBcc($bccAddress)
    {
        $this->laminasMessage->addBcc($bccAddress);
        return $this;
    }

    /**
     * Forward compatibility
     *
     * @inheritdoc
     */
    public function setBody($body)
    {
        $this->setPart($this->currentMimeType, $body);
        return $this;
    }

    /**
     * Forward compatibility
     *
     * @inheritdoc
     */
    public function setMessageType($type)
    {
        $this->currentMimeType = $type;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setReplyTo($replyToAddress)
    {
        $this->laminasMessage->setReplyTo($replyToAddress);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setBodyHtml($html)
    {
        return $this->setPart(\Laminas\Mime\Mime::TYPE_HTML, $html);
    }

    /**
     * @inheritdoc
     */
    public function setBodyText($text)
    {
        return $this->setPart(\Laminas\Mime\Mime::TYPE_TEXT, $text);
    }

    /**
     * @inheritdoc
     */
    public function setBodyAmp(string $ampHtml)
    {
        return $this->setPart(self::TYPE_AMP, $ampHtml);
    }

    /**
     * @inheritdoc
     */
    public function setBodyAttachment($content, $fileName, $fileType, $encoding = '8bit')
    {
        $part = $this->partsCollector->createPart((string)$fileType, $content, $encoding);
        $part->setFileName($fileName)
             ->setDisposition(\Laminas\Mime\Mime::DISPOSITION_ATTACHMENT);
        $this->partsCollector->addPartToList($part);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRecipientList() : array
    {
        return $this->emailAddressParser->getValidEmails($this->recipients);
    }
}
