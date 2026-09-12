<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Smtp;

class ModuleList
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ModuleList constructor.
     *
     * @param \Magento\Framework\Module\Manager                  $moduleManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleManager = $moduleManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isOnlyZendOne() : bool
    {
        $onlyZendOne = false;

        foreach ($this->getListOfModulesThatSupportOnlyZendOne() as $moduleInfo) {
            if ($this->moduleManager->isEnabled($moduleInfo['name'])
                && (! $moduleInfo['path'] || $this->scopeConfig->isSetFlag($moduleInfo['path']))
            ) {
                $onlyZendOne = true;
            }
        }

        return $onlyZendOne;
    }

    /**
     * @return array
     */
    private function getListOfModulesThatSupportOnlyZendOne() : array
    {
        return [
//            [
//                'name' => 'Company_ModuleName',
//                'path' => 'vendor/general/enabled',
//            ],
        ];
    }
}
