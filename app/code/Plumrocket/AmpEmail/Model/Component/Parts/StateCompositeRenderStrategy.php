<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\Parts;

class StateCompositeRenderStrategy implements \Plumrocket\AmpEmail\Model\ComponentPartRenderStrategyInterface
{
    const TYPE = 'state';

    const STATE_PART_PLACEHOLDER = '<!--@ pramp_email_states @-->';

    /**
     * @var array
     */
    private $renderStrategies;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * StateCompositeRenderStrategy constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param array                    $renderStrategies
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, array $renderStrategies = [])
    {
        $this->logger = $logger;
        $this->renderStrategies = $renderStrategies;
    }

    /**
     * @param array  $partContents
     * @param string $emailContent
     * @return string
     */
    public function render(array $partContents, string $emailContent) : string
    {
        foreach ($partContents as $stateType => $data) {
            $strategy = $this->renderStrategies[$stateType] ?? false;

            if ($strategy) {
                if ($strategy instanceof \Plumrocket\AmpEmail\Model\ComponentPartRenderStrategyInterface) {
                    $emailContent = $strategy->render($data, $emailContent);
                } else {
                    try {
                        $emailContent = $strategy->render($data, $emailContent);
                    } catch (\Exception $e) {
                        $this->logger->warning(
                            'AmpEmail :: invalid render strategy for component state type "' . $stateType .  '""'
                        );
                    }
                }
            } else {
                $this->logger->warning(
                    'AmpEmail :: cannot find render strategy for component state type "' . $stateType .  '""'
                );
            }
        }

        return $emailContent;
    }
}
