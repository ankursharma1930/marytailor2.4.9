<?php

namespace Dolphin\Productfaq\Model\ResourceModel\Products;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Dolphin\Productfaq\Model\Products as ModelProducts;
use Dolphin\Productfaq\Model\ResourceModel\Products as ResourceModelProduct;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ModelProducts::class,
            ResourceModelProduct::class
        );
    }
}
