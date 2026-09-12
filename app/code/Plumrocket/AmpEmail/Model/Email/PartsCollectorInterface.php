<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email;

interface PartsCollectorInterface
{
    /**
     * Create part for mime type
     *
     * @param string $messageMime
     * @param mixed  $content
     * @param string $encoding
     * @return \Laminas\Mime\Part
     */
    public function createPart(string $messageMime, $content, string $encoding) : \Laminas\Mime\Part;

    /**
     * Add part to list
     * Will replace part with same mime type
     *
     * @param \Laminas\Mime\Part $part
     * @return PartsCollectorInterface
     */
    public function addPartToList(\Laminas\Mime\Part $part) : self;

    /**
     * Get unique parts
     *
     * @return \Generator
     */
    public function getList() : \Generator;

    /**
     * Retrieve count of unique parts
     *
     * @return int
     */
    public function getCount() : int;

    /**
     * Add parts to mime message
     * Should applying only once for message
     *
     * TODO: need refactor after Gmail add support amp + attachment
     * @link https://docs.zendframework.com/zend-mail/message/attachments/
     *
     * @param \Laminas\Mime\Message $message
     * @return \Laminas\Mime\Message
     */
    public function applyToMessage(\Laminas\Mime\Message $message) : \Laminas\Mime\Message;

    /**
     * Check if exist AMP for Email Part
     *
     * @return bool
     */
    public function hasAmpForEmail() : bool;
}
