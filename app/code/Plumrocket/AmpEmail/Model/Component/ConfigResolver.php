<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component;

class ConfigResolver implements \Plumrocket\AmpEmail\Api\ComponentConfigResolverInterface
{
    /**
     * @var \Plumrocket\AmpEmail\Model\Component\Config\Data
     */
    private $dataStorage;

    /**
     * ConfigResolver constructor.
     *
     * @param \Plumrocket\AmpEmail\Model\Component\Config\Data $dataStorage
     */
    public function __construct(\Plumrocket\AmpEmail\Model\Component\Config\Data $dataStorage)
    {
        $this->dataStorage = $dataStorage;
    }

    /**
     * @param string $type
     * @return array
     */
    public function execute(string $type) : array
    {
        $widgets = $this->dataStorage->get();

        foreach ($widgets as $widget) {
            if (isset($widget['@']['type']) && $type === $widget['@']['type']) {
                return $widget;
            }
        }

        return [];
    }
}
