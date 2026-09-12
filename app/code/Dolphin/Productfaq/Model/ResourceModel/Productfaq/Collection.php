<?php

namespace Dolphin\Productfaq\Model\ResourceModel\Productfaq;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Dolphin\Productfaq\Model\Productfaq as ModelProductFaq;
use Dolphin\Productfaq\Model\ResourceModel\Productfaq as ResourceModelProductFaq;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'productfaq_id';
    /**
     * @var string
     */
    protected $_eventPrefix = 'dolphin_productfaq_productfaq_collection';
    /**
     * @var string
     */
    protected $_eventObject = 'dolphin_productfaq_productfaq_grid_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ModelProductFaq::class,
            ResourceModelProductFaq::class
        );
    }
}
