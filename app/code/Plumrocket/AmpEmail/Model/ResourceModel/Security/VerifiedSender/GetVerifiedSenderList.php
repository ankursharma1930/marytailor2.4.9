<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\ResourceModel\Security\VerifiedSender;

use Magento\Framework\App\ResourceConnection;
use Plumrocket\AmpEmail\Model\ResourceModel\Security\VerifiedSender as VerifiedSenderResource;

class GetVerifiedSenderList implements \Plumrocket\AmpEmail\Api\GetVerifiedSenderListInterface
{
    const CACHE_IDENTIFIER = 'PR_AMP_EMAIL_SENDERS';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array|null
     */
    private $emails;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @param ResourceConnection                    $resourceConnection
     * @param \Magento\Framework\App\CacheInterface $cache
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->cache = $cache;
    }

    /**
     * @param bool $reload
     * @return array
     */
    public function execute(bool $reload = false) : array
    {
        if ($reload || null === $this->emails) {
            if ($reload || ! ($emails = $this->cache->load(self::CACHE_IDENTIFIER))) {
                $connection = $this->resourceConnection->getConnection();
                $tableName = $this->resourceConnection->getTableName(
                    VerifiedSenderResource::MAIN_TABLE_NAME
                );

                $select = $connection->select()->from($tableName, 'email');
                $emails = $connection->fetchCol($select);

                $this->cache->save(
                    implode(',', $emails),
                    self::CACHE_IDENTIFIER,
                    [\Magento\Framework\App\Config::CACHE_TAG]
                );
            } else {
                $emails = explode(',', $emails);
            }

            $this->emails = $emails;
        }

        return $this->emails;
    }
}
