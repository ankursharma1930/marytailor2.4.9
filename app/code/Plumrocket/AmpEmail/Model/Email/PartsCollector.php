<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email;

use Laminas\Mime\PartFactory;

class PartsCollector implements PartsCollectorInterface
{
    /**
     * @var \Laminas\Mime\Part[]
     */
    private $parts = [];

    /**
     * Keeping object hashes of applied messages
     *
     * @var array
     */
    private $appliedMessages = [];

    /**
     * @var MimeTypeSorterInterface
     */
    private $mimeTypeSorter;

    /**
     * @var \Laminas\Mime\PartFactory
     */
    private $partFactory;

    /**
     * PartsCollector constructor.
     *
     * @param MimeTypeSorterInterface $mimeTypeSorter
     */
    public function __construct(
        MimeTypeSorterInterface $mimeTypeSorter,
        PartFactory $partFactory
    ) {
        $this->mimeTypeSorter = $mimeTypeSorter;
        $this->partFactory = $partFactory;
    }

    /**
     * @inheritDoc
     */
    public function createPart(string $messageMime, $content, string $encoding) : \Laminas\Mime\Part
    {
        /** @var \Laminas\Mime\Part $part */
        $part = $this->partFactory->create();

        $part->setContent($content);
        $part->setCharset($encoding);
        $part->setType($messageMime);

        return $part;
    }

    /**
     * @inheritDoc
     */
    public function addPartToList(\Laminas\Mime\Part $part) : PartsCollectorInterface
    {
        $this->parts[$part->getType()] = $part;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getList() : \Generator
    {
        yield from $this->mimeTypeSorter->sort($this->parts);
    }

    /**
     * @inheritDoc
     */
    public function getCount() : int
    {
        return count($this->parts);
    }

    /**
     * @inheritDoc
     */
    public function applyToMessage(\Laminas\Mime\Message $message) : \Laminas\Mime\Message
    {
        if (! $this->isAppliedToMessage($message)) {
            /** @var \Laminas\Mime\Part $part */
            foreach ($this->getList() as $part) {
                $message->addPart($part);
            }
            $this->saveApplyAction($message);
        }

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function hasAmpForEmail() : bool
    {
        return isset($this->parts[AmpMessage::TYPE_AMP]);
    }

    /**
     * @param \Laminas\Mime\Message $message
     * @return bool
     */
    private function isAppliedToMessage(\Laminas\Mime\Message $message) : bool
    {
        return in_array(spl_object_hash($message), $this->appliedMessages, true);
    }

    /**
     * @param \Laminas\Mime\Message $message
     * @return $this
     */
    private function saveApplyAction(\Laminas\Mime\Message $message) : self
    {
        $this->appliedMessages[] = spl_object_hash($message);

        return $this;
    }
}
