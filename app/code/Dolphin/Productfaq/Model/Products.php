<?php

namespace Dolphin\Productfaq\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Dolphin\Productfaq\Model\ResourceModel\Products as ResourceModelProducts;
use Magento\Framework\Model\AbstractModel;

class Products extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'dolphin_products_grid';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModelProducts::class);
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
