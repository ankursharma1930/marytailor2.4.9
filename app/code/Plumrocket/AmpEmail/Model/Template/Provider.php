<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Template;

use Magento\Framework\Exception\NoSuchEntityException;
use Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface;

/**
 * Class Provider
 *
 * Load and cache amp templates
 */
class Provider implements \Plumrocket\AmpEmail\Api\AmpTemplateProviderInterface
{
    /**
     * @var \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterfaceFactory
     */
    private $ampTemplateFactory;

    /**
     * @var \Plumrocket\AmpEmail\Model\Template\Config
     */
    private $ampEmailConfig;

    /**
     * @var array
     */
    private $instances = [];

    /**
     * AmpTemplateProvider constructor.
     *
     * @param \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterfaceFactory $ampTemplateFactory
     * @param \Plumrocket\AmpEmail\Model\Template\Config                   $ampEmailConfig
     */
    public function __construct(
        \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterfaceFactory $ampTemplateFactory,
        \Plumrocket\AmpEmail\Model\Template\Config $ampEmailConfig
    ) {
        $this->ampTemplateFactory = $ampTemplateFactory;
        $this->ampEmailConfig = $ampEmailConfig;
    }

    /**
     * @inheritDoc
     */
    public function getTemplate($templateId, bool $forceReload = false) : AmpTemplateInterface
    {
        if (! isset($this->instances[$templateId]) || $forceReload) {
            if (is_numeric($templateId)) {
                /** @var AmpTemplateInterface|\Plumrocket\AmpEmail\Model\Email\AmpTemplate $ampTemplate */
                $ampTemplate = $this->ampTemplateFactory->create();
                $ampTemplate->setId($templateId)->loadTemplate();
            } else {
                $parts = $this->ampEmailConfig->parseTemplateIdParts($templateId);
                $parsedTemplateId = $parts['templateId'];
                $theme = $parts['theme'];
                $area = $this->ampEmailConfig->getTemplateArea($parsedTemplateId);

                /** @var AmpTemplateInterface|\Plumrocket\AmpEmail\Model\Email\AmpTemplate $ampTemplate */
                // TODO: after left support 2.2, change set area via constructor to setForcedArea and refactor code
                $ampTemplate = $this->ampTemplateFactory->create(['data' => ['area' => $area]]);
                $ampTemplate->setId($templateId)->loadTemplate();

                if ($theme) {
                    $ampTemplate->setForcedTheme($parsedTemplateId, $theme);
                }
            }

            if (! $ampTemplate->getTemplateText()) {
                throw new NoSuchEntityException(
                    __('Unable to find Amp Template with ID "%1", or content is empty', $ampTemplate->getId())
                );
            }

            $this->instances[$templateId] = $ampTemplate;
        }

        return $this->instances[$templateId];
    }

    /**
     * @inheritDoc
     */
    public function isExistAmpForEmailById($templateId) : bool
    {
        try {
            $this->getTemplate($templateId);
        } catch (NoSuchEntityException $noSuchEntityException) {
            return false;
        }

        return true;
    }
}
