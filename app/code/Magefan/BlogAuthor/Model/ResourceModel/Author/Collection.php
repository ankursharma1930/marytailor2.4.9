<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Model\ResourceModel\Author;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magefan\Blog\Api\AuthorCollectionInterface;

/**
 * Blog author collection
 */
class Collection extends AbstractCollection implements AuthorCollectionInterface
{
    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'author_id';

    /**
     * @var int
     */
    protected $_storeId;

    /**
     * Constructor
     * Configures collection
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magefan\BlogAuthor\Model\Author::class, \Magefan\BlogAuthor\Model\ResourceModel\Author::class);
        $this->_map['fields']['author_id'] = 'main_table.author_id';
        $this->_map['fields']['store'] = 'store_table.store_id';
    }

    /**
     * Add field filter to collection
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (is_array($field)) {
            if (count($field) > 1) {
                return parent::addFieldToFilter($field, $condition);
            } elseif (count($field) === 1) {
                $field = $field[0];
                $condition = isset($condition[0]) ? $condition[0] : $condition;
            }
        }

        if ($field === 'store_id' || $field === 'store_ids') {
            return $this->addStoreFilter($condition);
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add store filter to collection
     * @param array|int|\Magento\Store\Model\Store  $store
     * @param boolean $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if ($store === null) {
            return $this;
        }

        if (!$this->getFlag('store_filter_added')) {
            if ($store instanceof \Magento\Store\Model\Store) {
                $this->_storeId = $store->getId();
                $store = [$store->getId()];
            }

            if (!is_array($store)) {
                $this->_storeId = $store;
                $store = [$store];
            }

            if (in_array(\Magento\Store\Model\Store::DEFAULT_STORE_ID, $store)) {
                return $this;
            }

            if ($withAdmin) {
                $store[] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            }

            $this->addFilter('store', ['in' => $store], 'public');
            $this->setFlag('store_filter_added', 1);
        }
        return $this;
    }

    /**
     * Get SQL for get record count
     *
     * Extra GROUP BY strip added.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(\Magento\Framework\DB\Select::GROUP);

        return $countSelect;
    }

    /**
     * Add active filter to collection
     * @return self
     */
    public function addActiveFilter()
    {
        return $this
            ->addFieldToFilter('main_table.is_active', \Magefan\BlogAuthor\Model\Author::STATUS_ENABLED);
    }

    /**
     * Perform operations after collection load
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $items = $this->getColumnValues('author_id');
        if (count($items)) {
            $connection = $this->getConnection();
            $tableName = $this->getTable('magefan_blog_author_store');
            $select = $connection->select()
                ->from(['cps' => $tableName])
                ->where('cps.author_id IN (?)', $items);

            $result = [];
            foreach ($connection->fetchAll($select) as $item) {
                if (!isset($result[$item['author_id']])) {
                    $result[$item['author_id']] = [];
                }
                $result[$item['author_id']][] = $item['store_id'];
            }

            if ($result) {
                foreach ($this as $item) {
                    $authorId = $item->getData('author_id');
                    if (!isset($result[$authorId])) {
                        continue;
                    }
                    if ($result[$authorId] == 0) {
                        $stores = $this->_storeManager->getStores(false, true);
                        $storeId = current($stores)->getId();
                    } else {
                        $storeId = $result[$item->getData('author_id')];
                    }
                    $item->setData('_first_store_id', $storeId);
                    $item->setData('store_ids', $result[$authorId]);
                }
            }

            if ($this->_storeId) {
                foreach ($this as $item) {
                    $item->setStoreId($this->_storeId);
                }
            }
        }

        return parent::_afterLoad();
    }

    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        if ($this->getFilter('store')) {
            $this->getSelect()->join(
                ['store_table' => $this->getTable('magefan_blog_author_store')],
                'main_table.author_id = store_table.author_id',
                []
            )->group(
                'main_table.author_id'
            );
        }
        parent::_renderFiltersBefore();
    }
}
