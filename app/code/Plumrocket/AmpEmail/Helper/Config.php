<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Plumrocket\Base\Model\ConfigUtils;

class Config extends AbstractHelper
{

    /**
     * @var int
     */
    public const AMP_TYPE = 3;

    public const XML_PATH_IS_MODULE_ENABLED = 'prampemail/general/enabled';

    /**
     * @var \Plumrocket\Base\Model\ConfigUtils
     */
    private $configUtils;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Plumrocket\Base\Model\ConfigUtils    $configUtils
     */
    public function __construct(Context $context, ConfigUtils $configUtils)
    {
        parent::__construct($context);
        $this->configUtils = $configUtils;
    }

    /**
     * Check if module enabled.
     *
     * @param null|string|int $store store view code or website code
     * @param string|null     $scope
     * @return bool
     */
    public function isModuleEnabled($store = null, string $scope = null): bool
    {
        return $this->configUtils->isSetFlag(self::XML_PATH_IS_MODULE_ENABLED, $store, $scope);
    }

    /**
     * Only for development.
     *
     * @param null|int|string $store
     * @return bool
     */
    public function isAllowRequestFromAmpPlayground($store = null) : bool
    {
        return false;
    }
}
