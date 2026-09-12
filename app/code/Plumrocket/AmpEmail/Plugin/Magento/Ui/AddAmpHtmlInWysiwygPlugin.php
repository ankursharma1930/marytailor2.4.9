<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Plugin\Magento\Ui;

class AddAmpHtmlInWysiwygPlugin
{
    /**
     * @var array|null
     */
    private $settings;

    /**
     * @var \Plumrocket\AmpEmail\Api\AmpComponentLibraryJsInterface
     */
    private $ampComponentLibraryJs;

    /**
     * @var array
     */
    private $extendedValidElements;

    /**
     * @var array
     */
    private $customElements;

    /**
     * @var array
     */
    private $validChildren;

    /**
     * AddAmpHtmlInWysiwygPlugin constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface               $objectManager
     * @param \Plumrocket\AmpEmail\Model\Magento\VersionProvider      $versionProvider
     * @param \Plumrocket\AmpEmail\Api\AmpComponentLibraryJsInterface $ampComponentLibraryJs
     * @param array                                                   $extendedValidElements
     * @param array                                                   $customElements
     * @param array                                                   $validChildren
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Plumrocket\AmpEmail\Model\Magento\VersionProvider $versionProvider,
        \Plumrocket\AmpEmail\Api\AmpComponentLibraryJsInterface $ampComponentLibraryJs,
        array $extendedValidElements = [],
        array $customElements = [],
        array $validChildren = []
    ) {
        $this->ampComponentLibraryJs = $ampComponentLibraryJs;
        $this->extendedValidElements = $extendedValidElements;
        $this->customElements = $customElements;
        $this->validChildren = $validChildren;
    }

    /**
     * @param \Magento\Ui\Component\Wysiwyg\ConfigInterface $configInterface
     * @param \Magento\Framework\DataObject $result
     * @return \Magento\Framework\DataObject
     */
    public function afterGetConfig(
        \Magento\Ui\Component\Wysiwyg\ConfigInterface $configInterface,
        \Magento\Framework\DataObject $result
    ) : \Magento\Framework\DataObject {
        $defaultSettings = (array) $result->getData('settings');

        if (! is_array($defaultSettings)) {
            $defaultSettings = [];
        }

        foreach ($this->getAmpSettings() as $type => $elements) {
            $value = implode(',', $elements);

            if (empty($defaultSettings[$type])) {
                $defaultSettings[$type] = $value;
            } else {
                $defaultSettings[$type] .= ',' . $value;
            }
        }

        $result->setData('settings', $defaultSettings);

        return $result;
    }

    /**
     * @return array
     */
    private function getAmpSettings() : array
    {
        if (null === $this->settings) {
            $settings = [
                'extended_valid_elements' => [],
                'custom_elements' => [],
                'valid_children' => [],
            ];

            foreach ($this->ampComponentLibraryJs->getList() as $item) {
                $tagName = $item['element'] ?? $item['type'];

                $settings['extended_valid_elements'][] = $tagName . '[*]';
                $settings['custom_elements'][] = $tagName;
            }

            $settings['extended_valid_elements'] = array_merge(
                $settings['extended_valid_elements'],
                $this->extendedValidElements
            );
            $settings['custom_elements'] = array_merge(
                $settings['custom_elements'],
                $this->customElements
            );
            $settings['valid_children'] = array_merge(
                $settings['valid_children'],
                $this->validChildren
            );

            $this->settings = $settings;
        }

        return $this->settings;
    }
}
