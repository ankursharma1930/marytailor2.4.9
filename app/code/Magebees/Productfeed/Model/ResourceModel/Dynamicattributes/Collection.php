<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Model\ResourceModel\Dynamicattributes;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magebees\Productfeed\Model\Dynamicattributes', 'Magebees\Productfeed\Model\ResourceModel\Dynamicattributes');
    }
}
