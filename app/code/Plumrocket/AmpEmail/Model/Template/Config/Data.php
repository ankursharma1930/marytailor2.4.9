<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Template\Config;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Provides amp email templates configuration
 */
class Data extends \Magento\Framework\Config\Data
{
    /**
     * Data constructor.
     *
     * @param Reader                                   $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param SerializerInterface                      $serializer
     * @param string                                   $cacheId
     */
    public function __construct(
        \Plumrocket\AmpEmail\Model\Template\Config\Reader $reader,
        \Magento\Framework\Config\CacheInterface $cache,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        $cacheId = 'pramp_email_templates'
    ) {
        parent::__construct($reader, $cache, $cacheId, $serializer);
    }
}
