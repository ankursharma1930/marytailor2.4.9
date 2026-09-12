<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel\PublishPermissionPlugin;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Processor
 */
class Processor
{
    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * Processor constructor.
     * @param AuthorizationInterface $authorization
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        AuthorizationInterface $authorization,
        ManagerInterface $messageManager
    ) {
        $this->authorization = $authorization;
        $this->messageManager = $messageManager;
    }

    /**
     * Check if action is allowed
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @param $object
     * @param $adminResource
     * @param $isActiveFieldName
     * @throws LocalizedException
     */
    public function execute(
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource,
        $object,
        $adminResource,
        $isActiveFieldName
    ) {
        if ($this->authorization->isAllowed($adminResource)) {
            return;
        }

        if ($object->getId()) {
            $connection = $resource->getConnection();
            $sql = $connection->select()
                ->from(['cps' => $resource->getMainTable()], [$isActiveFieldName])
                ->where('cps.' . $resource->getIdFieldName() . ' IN (?)', $object->getId());
            $data = $connection->fetchAll($sql);
            $isActive = isset($data[0][$isActiveFieldName]) ? $data[0][$isActiveFieldName] : null;

            if (1 == $isActive) {
                throw new LocalizedException(__('You do not have permission to update published item(s).'));
            }
        }

        if (1 == $object->getData($isActiveFieldName)) {
            $object->setData($isActiveFieldName, 0);
            $this->messageManager->addNotice(__('Item has been saved but not published as you do not have permission to publish.'));
        }
    }
}
