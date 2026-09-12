<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model;

/**
 * Interface MinimizeCssInterface
 *
 * @since 1.0.1
 */
class CssMinify implements \Magento\Framework\Code\Minifier\AdapterInterface
{
    /**
     * The Internet Message Format RFC the latest of which is 5322
     *
     * 2.1.1. Line Length Limits
     * @link http://www.rfc-editor.org/rfc/rfc5322.txt
     */
    const MAX_LINE_LENGTH = 998;
    const RECOMMENDED_LENGTH = 78;

    /**
     * Let some character for tags and whitespaces around css line
     */
    const SAFE_CSS_LENGTH = self::MAX_LINE_LENGTH - 200;

    /**
     * @param string $content
     * @param int    $maxStringLength
     * @return string
     */
    public function minify($content, int $maxStringLength = self::SAFE_CSS_LENGTH) : string
    {
        if ($content) {
            $content = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $content); // negative look ahead
            $content = preg_replace('/\s{2,}/', ' ', $content);
            $content = preg_replace('/\s*([:;{}])\s*/', '$1', $content);
            $content = preg_replace('/;}/', '}', $content);
            $content =  $this->prepareLength($content, $maxStringLength);
        }

        return $content;
    }

    /**
     * @param string $content
     * @param int    $maxStringLength
     * @return string
     */
    private function prepareLength(string $content, int $maxStringLength) : string
    {
        $contentLen = strlen($content);
        if ($contentLen > $maxStringLength) {
            $cssLines = explode('}', $content);
            $result = [];
            $resultLine = '';
            $resultLineLen = 0;
            $count = count($cssLines);
            $currentNumber = 1;

            foreach ($cssLines as $cssLine) {
                $isLast = $count === $currentNumber++;
                $cssLine = $isLast ? $cssLine : $cssLine . '}';
                $len = strlen($cssLine);

                if ($len > $maxStringLength) {
                    // todo: add logic for long css part (.some-class{...})
                    $result[] = $cssLine;
                } elseif ($resultLineLen + $len > $maxStringLength) {
                    $result[] = $resultLine;
                    $resultLine = $cssLine;
                    $resultLineLen = $len;
                } else {
                    $resultLine .= $cssLine;
                    $resultLineLen += $len;
                }

                if ($isLast) {
                    $result[] = $resultLine;
                }
            }

            $content = implode("\n", array_filter($result));
        }

        return $content;
    }
}
