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

class AddVerifiedSender implements \Plumrocket\AmpEmail\Api\AddVerifiedSenderInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Plumrocket\AmpEmail\Api\GetVerifiedSenderListInterface
     */
    private $getVerifiedSenderList;

    /**
     * AddVerifiedSender constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection               $resourceConnection
     * @param \Plumrocket\AmpEmail\Api\GetVerifiedSenderListInterface $getVerifiedSenderList
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        \Plumrocket\AmpEmail\Api\GetVerifiedSenderListInterface $getVerifiedSenderList
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getVerifiedSenderList = $getVerifiedSenderList;
    }

    /**
     * @param array $emails
     * @return bool
     */
    public function execute(array $emails) : bool
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(
            VerifiedSenderResource::MAIN_TABLE_NAME
        );

        $existsEmails = $this->getVerifiedSenderList->execute();

        $emails = array_unique(array_filter($emails));

        $emailsForInsert = [];
        foreach ($emails as $index => $email) {
            if (! in_array($email, $existsEmails, true)) {
                $emailsForInsert = ['email' => $emails[$index]];
            }
        }

        if (! $emailsForInsert) {
            return false;
        }

        $added = (bool) $connection->insertMultiple($tableName, $emailsForInsert);
        if ($added) {
            $this->getVerifiedSenderList->execute(true);
        }

        return $added;
    }
}
