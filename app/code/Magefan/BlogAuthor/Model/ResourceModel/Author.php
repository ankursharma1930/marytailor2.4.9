<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magefan\Blog\Api\AuthorResourceModelInterface;

/**
 * Blog author resource model
 */
class Author extends AbstractDb implements AuthorResourceModelInterface
{

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * Author constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param null $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->_date = $date;
        $this->dateTime = $dateTime;
    }


    /**
     * Initialize resource model
     * Get tablename from config
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magefan_blog_author', 'author_id');
    }


    /**
     * Process author data before saving
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        foreach (['creation_time', 'update_time', 'custom_theme_from', 'custom_theme_to'] as $field) {
            $value = $object->getData($field) ?: null;
            $object->setData($field, $this->dateTime->formatDate($value));
        }

        // check author url
        if (!$object->getData('identifier')) {
            $object->setData('identifier', strtolower($object->getFirstname().'-'.$object->getLastname()));
        }

        $id = $this->checkIdentifier($object->getData('identifier'));
        if ($id && $id !== $object->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('URL key is already in use by another author.')
            );
        }

        $identifierGenerator = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Magefan\Blog\Model\ResourceModel\PageIdentifierGenerator');
        $identifierGenerator->generate($object);

        if (!$this->isValidPageIdentifier($object)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The post URL key contains disallowed symbols.')
            );
        }

        if ($this->isNumericPageIdentifier($object)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The post URL key cannot be made of only numbers.')
            );
        }

        $gmtDate = $this->_date->gmtDate();

        if ($object->isObjectNew() && !$object->getCreationTime()) {
            $object->setCreationTime($gmtDate);
        }


        $object->setUpdateTime($gmtDate);

        return parent::_beforeSave($object);
    }

    /**
     * Assign author to store views
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $oldIds = $this->lookupStoreIds($object->getId());
        $newIds = (array)$object->getStoreIds();
        if (!$newIds || in_array(0, $newIds)) {
            $newIds = [0];
        }

        $this->_updateLinks($object, $newIds, $oldIds, 'magefan_blog_author_store', 'store_id');

        return parent::_afterSave($object);
    }

    /**
     * Perform operations after object load
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId()) {
            $storeIds = $this->lookupStoreIds($object->getId());
            $object->setData('store_ids', $storeIds);
        }

        return parent::_afterLoad($object);
    }

    /**
     * Get store ids to which specified item is assigned
     *
     * @param int $authorId
     * @return array
     */
    public function lookupStoreIds($authorId)
    {
        return $this->_lookupIds($authorId, 'magefan_blog_author_store', 'store_id');
    }

    /**
     * Get ids to which specified item is assigned
     * @param  int $authorId
     * @param  string $tableName
     * @param  string $field
     * @return array
     */
    protected function _lookupIds($authorId, $tableName, $field)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()->from(
            $this->getTable($tableName),
            $field
        )->where(
            'author_id = ?',
            (int)$authorId
        );

        return $adapter->fetchCol($select);
    }

    /**
     * Update author connections
     * @param  \Magento\Framework\Model\AbstractModel $object
     * @param  Array $newRelatedIds
     * @param  Array $oldRelatedIds
     * @param  String $tableName
     * @param  String  $field
     * @param  Array  $rowData
     * @return void
     */
    protected function _updateLinks(
        \Magento\Framework\Model\AbstractModel $object,
        array $newRelatedIds,
        array $oldRelatedIds,
        $tableName,
        $field,
        $rowData = []
    ) {
        $table = $this->getTable($tableName);

        if ($object->getId() && empty($rowData)) {
            $currentData = $this->_lookupAll($object->getId(), $tableName, '*');
            foreach ($currentData as $item) {
                $rowData[$item[$field]] = $item;
            }
        }

        $insert = $newRelatedIds;
        $delete = $oldRelatedIds;

        if ($delete) {
            $where = ['author_id = ?' => (int)$object->getId(), $field.' IN (?)' => $delete];

            $this->getConnection()->delete($table, $where);
        }

        if ($insert) {
            $data = [];

            foreach ($insert as $id) {
                $id = (int)$id;
                $data[] = array_merge(
                    ['author_id' => (int)$object->getId(), $field => $id],
                    (isset($rowData[$id]) && is_array($rowData[$id])) ? $rowData[$id] : []
                );
            }

            /* Fix if some rows have extra data */
            $allFields = [];
            foreach ($data as $i => $row) {
                foreach ($row as $key => $value) {
                    $allFields[$key] = $key;
                }
            }
            foreach ($data as $i => $row) {
                foreach ($allFields as $key) {
                    if (!array_key_exists($key, $row)) {
                        $data[$i][$key] = null;
                    }
                }
            }
            /* End fix */

            $this->getConnection()->insertMultiple($table, $data);
        }
    }

    /**
     * Get rows to which specified item is assigned
     * @param  int $authorId
     * @param  string $tableName
     * @param  string $field
     * @return array
     */
    protected function _lookupAll($authorId, $tableName, $field)
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()->from(
            $this->getTable($tableName),
            $field
        )->where(
            'author_id = ?',
            (int)$authorId
        );

        return $adapter->fetchAll($select);
    }

    /**
     * Check if author identifier exist
     * return author id if author exists
     *
     * @param string $identifier
     * @return int
     */
    public function checkIdentifier($identifier)
    {
        $select = $this->getConnection()->select()->from(
            ['cp' => $this->getMainTable()]
        )->where(
            'cp.identifier = ?',
            $identifier
        );

        $select->reset(\Zend_Db_Select::COLUMNS)->columns(['cp.author_id', 'cp.identifier'])->limit(1);

        $row = $this->getConnection()->fetchRow($select);
        if (isset($row['author_id']) && isset($row['identifier'])
            && $row['identifier'] == $identifier) {
            return (string)$row['author_id'];
        }

        return false;
    }

    /**
     *  Check whether author identifier is valid
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return bool
     */
    protected function isValidPageIdentifier(\Magento\Framework\Model\AbstractModel $object)
    {
        return preg_match('/^([^?#<>@!&*()$%^\\/+=,{}]+)?$/', $object->getData('identifier'));
    }

    /**
     *  Check whether post identifier is numeric
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return bool
     */
    protected function isNumericPageIdentifier(\Magento\Framework\Model\AbstractModel $object)
    {
        return preg_match('/^[0-9]+$/', $object->getData('identifier'));
    }
}
