<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\ItemProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sitemap\Model\ItemProvider\ConfigReaderInterface;

class RecipeSiteMapConfigReader implements ConfigReaderInterface
{
    /**#@+
     * Xpath config settings
     */
    const XML_PATH_CHANGE_FREQUENCY = 'sitemap/mageside_recipe/changefreq';
    const XML_PATH_PRIORITY = 'sitemap/mageside_recipe/priority';
    /**#@-*/

    /**
     * Scope config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * CategoryItemResolverConfigReader constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority($storeId)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_PRIORITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getChangeFrequency($storeId)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CHANGE_FREQUENCY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
