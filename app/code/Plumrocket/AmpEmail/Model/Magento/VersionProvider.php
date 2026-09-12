<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Magento;

class VersionProvider
{
    const CACHE_IDENTIFIER = 'PR_MAGENTO_VERSION';

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $magentoVersionLocalCache;

    /**
     * VersionProvider constructor.
     *
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\App\CacheInterface           $cache
     */
    public function __construct(
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        $this->productMetadata = $productMetadata;
        $this->cache = $cache;
    }

    /**
     * @param $version
     * @return bool
     */
    public function isMagentoVersionBelow($version) : bool
    {
        return -1 === version_compare($this->getMagentoVersion(), $version);
    }

    /**
     * @return string
     */
    public function getMagentoVersion() : string
    {
        if (! $this->magentoVersionLocalCache) {
            $magentoVersion = $this->cache->load(self::CACHE_IDENTIFIER);

            if (! $magentoVersion) {
                $magentoVersion = $this->productMetadata->getVersion();

                $this->cache->save(
                    $magentoVersion,
                    self::CACHE_IDENTIFIER,
                    [\Magento\Framework\App\Config::CACHE_TAG]
                );
            }

            $this->magentoVersionLocalCache = $magentoVersion;
        }

        return $this->magentoVersionLocalCache;
    }
}
