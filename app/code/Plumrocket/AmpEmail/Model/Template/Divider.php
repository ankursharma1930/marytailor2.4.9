<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Template;

class Divider
{
    const DELIMITER_STYLE_START = '<!--@pramp-styles-start@-->';
    const DELIMITER_STYLE_END = '<!--@pramp-styles-end@-->';

    /**
     * @param string $emailTemplateContent
     * @return array
     */
    public function divideIntoParts(string $emailTemplateContent) : array
    {
        if (preg_match($this->getStylesPattern(), $emailTemplateContent, $styles)) {
            $emailTemplateContent = str_replace($styles[0], '', $emailTemplateContent);
        }

        return [
            'content' => trim($emailTemplateContent),
            'styles' => trim($styles[1] ?? ''),
        ];
    }

    /**
     * @return string
     */
    private function getStylesPattern() : string
    {
        return '#' . self::DELIMITER_STYLE_START .
            '(?:\s*<style.*?>)([\s\S]*?)(?:<\/style>\s*)' .
            self::DELIMITER_STYLE_END . '#U';
    }
}
