<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel;

class Writer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('ms_recipe_writer', 'id');
    }

    /**
     * @param array $ids
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getWritersDataByIds($ids = [])
    {
        $select = $this->getConnection()->select()
            ->from(
                [$this->getMainTable()]
            )->where('customer_id IN (?)', $ids);

        $items = $this->getConnection()->fetchAll($select);

        $result = [];
        foreach ($items as $item) {
            $result[$item['customer_id']] = $item;
        }

        return $result;
    }
}
