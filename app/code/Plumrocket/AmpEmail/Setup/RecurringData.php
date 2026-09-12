<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class RecurringData implements InstallDataInterface
{
    /**
     * @var \Plumrocket\AmpEmail\Model\AddAmpAlternativesToTemplatesInterface
     */
    private $addAmpAlternativesToTemplates;

    /**
     * RecurringData constructor.
     *
     * @param \Plumrocket\AmpEmail\Model\AddAmpAlternativesToTemplatesInterface $addAmpAlternativesToTemplates
     */
    public function __construct(
        \Plumrocket\AmpEmail\Model\AddAmpAlternativesToTemplatesInterface $addAmpAlternativesToTemplates
    ) {
        $this->addAmpAlternativesToTemplates = $addAmpAlternativesToTemplates;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->addAmpAlternativesToTemplates->execute();
    }
}
