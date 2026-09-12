<?php

namespace Dolphin\Productfaq\Model\Mail;

use Magento\Framework\Mail\Template\TransportBuilder as MailTransportBuilder;

class TransportBuilder extends MailTransportBuilder
{
    /**
     * Adds an attachment to the email message.
     *
     * @param string $file The path to the file to attach.
     * @param string $name The name to assign to the attachment.
     * @return $this|bool Returns $this if attachment added successfully, otherwise false.
     */
    public function addAttachment($file, $name)
    {
        if ($file) {
            $this->message->createAttachment(
                //@codingStandardsIgnoreStart
                file_get_contents($file),
                //@codingStandardsIgnoreEnd
                \Zend_Mime::TYPE_OCTETSTREAM,
                \Zend_Mime::DISPOSITION_ATTACHMENT,
                \Zend_Mime::ENCODING_BASE64,
                //@codingStandardsIgnoreStart
                basename($name)
                //@codingStandardsIgnoreEnd
            );
            return $this;
        } else {
            return false;
        }
    }
}
