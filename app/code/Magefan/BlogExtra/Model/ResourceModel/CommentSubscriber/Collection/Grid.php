<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Model\ResourceModel\CommentSubscriber\Collection;

use Magefan\BlogExtra\Model\ResourceModel\CommentSubscriber\Collection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Store\Model\StoreManagerInterface;

class Grid extends Collection
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var int
     */
    protected $_storeId;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager,
     * @param AdapterInterface $connection
     * @param AbstractDb $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->joinLeft(
                ['post' => $this->getTable('magefan_blog_post')],
                'main_table.post_id = post.post_id',
                ['title' => 'title']
            );

        return $this;
    }

    /**
     * Perform operations after collection load
     *
     * @return Collection
     */
    protected function _afterLoad()
    {
        $items = $this->getColumnValues('post_id');
        if (count($items)) {
            $connection = $this->getConnection();
            $tableName = $this->getTable('magefan_blog_post_store');
            $select = $connection->select()
                ->from(['cps' => $tableName])
                ->where('cps.post_id IN (?)', $items);

            $result = [];
            foreach ($connection->fetchAll($select) as $item) {
                if (!isset($result[$item['post_id']])) {
                    $result[$item['post_id']] = [];
                }
                $result[$item['post_id']][] = $item['store_id'];
            }

            if ($result) {
                foreach ($this as $item) {
                    $postId = $item->getData('post_id');
                    if (!isset($result[$postId])) {
                        continue;
                    }
                    if ($result[$postId] == 0) {
                        $stores = $this->_storeManager->getStores(false, true);
                        $storeId = current($stores)->getId();
                    } else {
                        $storeId = $result[$item->getData('post_id')];
                    }
                    $item->setData('_first_store_id', $storeId);
                    $item->setData('store_ids', $result[$postId]);
                }
            }

            foreach ($this as $item) {
                if ($this->_storeId) {
                    $item->setStoreId($this->_storeId);
                }
            }
        }

        $this->_previewFlag = false;
        return parent::_afterLoad();
    }
}
