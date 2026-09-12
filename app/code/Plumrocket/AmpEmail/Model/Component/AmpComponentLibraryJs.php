<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component;

class AmpComponentLibraryJs implements \Plumrocket\AmpEmail\Api\AmpComponentLibraryJsInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * AmpComponentLibraryJs constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getList() : array
    {
        return [
            'amp-form' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-form-0.1.js',
                'type' => 'amp-form',
            ],
            'amp-selector' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-selector-0.1.js',
                'type' => 'amp-selector',
            ],
            'amp-bind' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-bind-0.1.js',
                'type' => 'amp-bind',
            ],
            'amp-list' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-list-0.1.js',
                'type' => 'amp-list',
            ],

            'amp-mustache' => [
                'src'     => 'https://cdn.ampproject.org/v0/amp-mustache-0.2.js',
                'type'    => 'amp-mustache',
                'element' => 'template',
            ],

            'amp-accordion' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-accordion-0.1.js',
                'type' => 'amp-accordion',
            ],
            'amp-carousel' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-carousel-0.1.js',
                'type' => 'amp-carousel',
            ],
            'amp-fit-text' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-fit-text-0.1.js',
                'type' => 'amp-fit-text',
            ],
            'amp-sidebar' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-sidebar-0.1.js',
                'type' => 'amp-sidebar',
            ],
            'amp-timeago' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-timeago-0.1.js',
                'type' => 'amp-timeago',
            ],

            'amp-anim' => [
                'src'  => 'https://cdn.ampproject.org/v0/amp-anim-0.1.js',
                'type' => 'amp-anim',
            ],
        ];
    }

    /**
     * @param string $ampEmailContent
     * @return array
     */
    public function detectUsedAmpComponents(string $ampEmailContent) : array
    {
        $allLibraries = $this->getList();

        $usedTypes = [];
        foreach ($allLibraries as $library => $data) {
            if ($library === 'amp-form' || $library === 'amp-bind') {
                continue;
            }

            if (isset($data['element']) && strpos($ampEmailContent, '<' . $data['element']) !== false) {
                $usedTypes[] = $library;
                continue;
            }

            if (strpos($ampEmailContent, '<' . $library) !== false) {
                $usedTypes[] = $library;
            }
        }

        if (strpos($ampEmailContent, '<form') !== false) {
            $usedTypes[] = 'amp-form';
        }

        if (strpos($ampEmailContent, 'AMP.setState') !== false
            || strpos($ampEmailContent, 'amp-state') !== false) {
            $usedTypes[] = 'amp-bind';
        }

        return $usedTypes;
    }

    /**
     * @param string $ampEmailContent
     * @param array  $libraryList
     * @return string
     */
    public function renderIntoEmailContent(string $ampEmailContent, array $libraryList) : string
    {
        $libraryList = array_filter(array_unique($libraryList));

        $librariesHtml = '';
        foreach ($libraryList as $type) {
            $librariesHtml .= $this->generateLibraryIncludeHtml($type);
        }

        return str_replace('<!--@ pramp_email_js_components @-->', $librariesHtml, $ampEmailContent);
    }

    /**
     * @param string $type
     * @return string
     */
    public function generateLibraryIncludeHtml(string $type) : string
    {
        $list = $this->getList();

        if (! isset($list[$type])) {
            $this->logger->warning("AmpEmail: amp library '{$type}' not found in list.");
            return '';
        }

        $library = $list[$type];
        $libraryType = $library['element'] ?? 'element';

        return "<script async custom-{$libraryType}=\"{$library['type']}\" src=\"{$library['src']}\"></script>\n";
    }
}
