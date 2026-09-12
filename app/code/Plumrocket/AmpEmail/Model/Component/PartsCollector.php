<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component;

use Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface;

class PartsCollector implements \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface
{
    /**
     * @var array
     */
    private $parts = [];

    /**
     * @var array
     */
    private $renderStrategies;

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * PartsCollector constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param array                    $renderStrategies
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, array $renderStrategies = [])
    {
        $this->renderStrategies = $renderStrategies;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function addPartToList(string $type, $part, $key = null) : ComponentPartsCollectorInterface
    {
        $this->count++;

        if (null !== $key) {
            if (isset($this->parts[$type][$key]) && is_array($this->parts[$type][$key])) {
                $this->parts[$type][$key] = array_merge($this->parts[$type][$key], $part);
            } else {
                $this->parts[$type][$key] = $part;
            }
        } else {
            $this->parts[$type][] = $part;
        }

        return $this;
    }

    /**
     * @return \Generator
     */
    public function getGroupedParts() : \Generator
    {
        yield from $this->parts;
    }

    /**
     * @return int
     */
    public function getCount() : int
    {
        return $this->count;
    }

    /**
     * @param string $ampEmailContent
     * @return string
     */
    public function renderIntoEmailContent(string $ampEmailContent) : string
    {
        foreach ($this->getGroupedParts() as $type => $part) {
            $strategy = $this->renderStrategies[$type] ?? false;

            if ($strategy) {
                if ($strategy instanceof \Plumrocket\AmpEmail\Model\ComponentPartRenderStrategyInterface) {
                    $ampEmailContent = $strategy->render($part, $ampEmailContent);
                } else {
                    try {
                        $ampEmailContent = $strategy->render($part, $ampEmailContent);
                    } catch (\Exception $e) {
                        $this->logger->warning(
                            'AmpEmail :: invalid render strategy for component part type "' . $type .  '""'
                        );
                    }
                }
            } else {
                $this->logger->warning(
                    'AmpEmail :: cannot find render strategy for component part type "' . $type .  '""'
                );
            }
        }

        return $ampEmailContent;
    }
}
