<?php

namespace Dolphin\Productfaq\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Productfaq extends AbstractDb
{

    public const TBL_ATT_PRODUCT = 'dolphin_productfaqgrid_rel';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dolphin_productfaq_productfaq', 'productfaq_id');
    }
}
