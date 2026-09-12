<?php

namespace Dolphin\Productfaq\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Dolphin\Productfaq\Model\ResourceModel\Productfaq as ResourceModelProductFaq;
use Dolphin\Productfaq\Model\Productfaq as ModelProductFaq;
use Magento\Framework\Model\AbstractModel;

class Productfaq extends AbstractModel implements IdentityInterface
{
    /**
     * CMS page cache tag
     */
    public const CACHE_TAG = 'dolphin_products_grid';
    /**
     * @var string
     */
    protected $_cacheTag = 'dolphin_products_grid';
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'dolphin_products_grid';
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModelProductFaq::class);
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

    /**
     * Retrieve product IDs associated with a product FAQ entry
     *
     * @param ModelProductFaq $object
     * @return array Product IDs
     */
    public function getProducts(ModelProductFaq $object)
    {
        $tbl = $this->getResource()->getTable(ResourceModelProductFaq::TBL_ATT_PRODUCT);
        $select = $this->getResource()->getConnection()->select()->from(
            $tbl,
            ['product_id']
        )
            ->where(
                'productfaq_id = ?',
                (int) $object->getId()
            );
        return $this->getResource()->getConnection()->fetchCol($select);
    }
}
