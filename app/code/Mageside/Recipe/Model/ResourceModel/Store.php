<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel;

class Store extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('ms_recipe_store', 'recipe_id');
    }

    public function clearStoresById($id)
    {
        $this->getConnection()->delete($this->getMainTable(), ['recipe_id = ?' => $id]);

        return $this;
    }

    public function saveChoosedStore($storeData)
    {
        $this->getConnection()->insertMultiple($this->getMainTable(), $storeData);

        return $this;
    }

    /**
     * Getting stores for prepare data for the recipe reviews
     * @param $id
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRecipeStores($id)
    {
        $select = $this->getConnection()->select()
            ->from(
                [$this->getMainTable()],
                ['store_id']
            )->where('recipe_id = ?', $id);

        $result = $this->getConnection()->fetchCol($select);

        return $result;
    }
}
