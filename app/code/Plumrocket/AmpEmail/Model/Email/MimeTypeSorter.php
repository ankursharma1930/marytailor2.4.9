<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email;

use Laminas\Mime\Mime;
use Plumrocket\AmpEmail\Model\Email\AmpMessage;

class MimeTypeSorter implements MimeTypeSorterInterface
{
    /**
     * Sort email message parts by mime types
     *
     * @param \Laminas\Mime\Part[] $parts
     * @return \Laminas\Mime\Part[]
     */
    public function sort(array $parts) : array
    {
        uasort($parts, [$this, 'sortParts']);

        return $parts;
    }

    /**
     * @param \Laminas\Mime\Part $firstPart
     * @param \Laminas\Mime\Part $secondPart
     * @return int
     */
    private function sortParts($firstPart, $secondPart)
    {
        if (Mime::TYPE_TEXT === $firstPart->getType()) {
            return -1;
        }

        if ($firstPart->getType() === AmpMessage::TYPE_AMP) {
            return Mime::TYPE_TEXT === $secondPart->getType() ? 1 : -1;
        }

        if ($firstPart->getType() === Mime::TYPE_HTML) {
            return in_array($secondPart->getType(), [Mime::TYPE_TEXT, AmpMessage::TYPE_AMP], true) ? 1 : -1;
        }

        return 1;
    }
}
