<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Recipe\Filter;

class Store extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('ms_recipe_filter_store', 'filter_id');
    }

    /**
     * @param $id
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function clearStoresById($id)
    {
        $this->getConnection()->delete($this->getMainTable(), ['filter_id = ?' => $id]);

        return $this;
    }

    /**
     * @param $storeData
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveChoosedStore($storeData)
    {
        $this->getConnection()->insertMultiple($this->getMainTable(), $storeData);

        return $this;
    }

    /**
     * @param $id
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFilterStores($id)
    {
        $select = $this->getConnection()->select()
            ->from(
                [$this->getMainTable()],
                ['store_id']
            )->where('filter_id = ?', $id);

        $result = $this->getConnection()->fetchCol($select);

        return $result;
    }
}
