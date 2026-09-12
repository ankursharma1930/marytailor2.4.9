<?php

namespace Dolphin\Productfaq\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Products extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dolphin_productfaqgrid_rel', 'id');
    }
}
