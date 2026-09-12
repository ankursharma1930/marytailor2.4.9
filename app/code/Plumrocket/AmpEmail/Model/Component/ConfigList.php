<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component;

class ConfigList
{
    /**
     * @var \Plumrocket\AmpEmail\Model\Component\Config\Data
     */
    private $dataStorage;

    /**
     * ConfigList constructor.
     *
     * @param \Plumrocket\AmpEmail\Model\Component\Config\Data $dataStorage
     */
    public function __construct(\Plumrocket\AmpEmail\Model\Component\Config\Data $dataStorage)
    {
        $this->dataStorage = $dataStorage;
    }

    /**
     * @param array $filters
     * @return array
     */
    public function execute(array $filters = []) : array
    {
        $widgets = $this->dataStorage->get();

        return $this->filterByParams($widgets, $filters);
    }

    /**
     * @param array $widgets
     * @param array $filters
     * @return array
     */
    private function filterByParams(array $widgets, array $filters) : array
    {
        $result = $widgets;

        if (! empty($filters)) {
            foreach ($widgets as $code => $widget) {
                foreach ($filters as $field => $value) {
                    if (!isset($widget[$field]) || (string)$widget[$field] != $value) {
                        unset($result[$code]);
                        break;
                    }
                }
            }
        }

        return $result;
    }
}
