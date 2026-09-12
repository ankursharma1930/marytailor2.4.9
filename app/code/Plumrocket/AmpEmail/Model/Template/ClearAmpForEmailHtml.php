<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Template;

class ClearAmpForEmailHtml implements \Plumrocket\AmpEmail\Api\ClearAmpForEmailHtmlInterface
{
    /**
     * @inheritDoc
     */
    public function execute(string $html) : string
    {
        $html = preg_replace(
            '#<a\s*?href=[\'"]tel:[\s\S]*?>([\s\S]*?)<\/a>#',
            '<span>$1</span>',
            $html
        );

        return $html;
    }
}
