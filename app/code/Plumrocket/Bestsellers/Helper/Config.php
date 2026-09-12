<?php
/**
 * @package     Plumrocket_Bestsellers
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Bestsellers\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Plumrocket\Base\Model\ConfigUtils;

/**
 * @since 1.1.0
 */
class Config extends AbstractHelper
{

    public const SECTION_ID = 'pr_bestsellers';
    public const XML_PATH_IS_MODULE_ENABLED = 'pr_bestsellers/general/enabled';

    /**
     * @var \Plumrocket\Base\Model\ConfigUtils
     */
    private $configUtils;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Plumrocket\Base\Model\ConfigUtils    $configUtils
     */
    public function __construct(
        Context $context,
        ConfigUtils $configUtils
    ) {
        parent::__construct($context);
        $this->configUtils = $configUtils;
    }

    /**
     * Check if module enabled for current|specific store
     *
     * @param string|int|null $store
     * @return bool
     */
    public function isModuleEnabled($store = null) : bool
    {
        return $this->configUtils->isSetFlag(self::XML_PATH_IS_MODULE_ENABLED, $store);
    }
}
