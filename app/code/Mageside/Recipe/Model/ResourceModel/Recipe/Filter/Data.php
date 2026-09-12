<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Recipe\Filter;

class Data extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('ms_recipe_filter_data', 'id');
    }

    public function clearFiltersById($id)
    {
        $this->getConnection()->delete($this->getMainTable(), ['recipe_id = ?' => $id]);

        return $this;
    }

    public function saveFilterData($data)
    {
        $this->getConnection()->insertMultiple($this->getMainTable(), $data);

        return $this;
    }
}
