<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Writer;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Mageside\Recipe\Model\Writer::class,
            \Mageside\Recipe\Model\ResourceModel\Writer::class
        );
    }

    public function addWriterFilter()
    {
        $this->getSelect()
            ->join(
                ['customer' => $this->getTable('customer_grid_flat')],
                'main_table.customer_id = customer.entity_id AND main_table.is_writer = 1',
                ['name']
            );

        return $this;
    }

    public function getWriterRecipe()
    {
        $this->getSelect()
            ->join(
                ['customer' => $this->getTable('customer_grid_flat')],
                'main_table.customer_id = customer.entity_id ',
                ['name']
            );

        return $this;
    }
}
