<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RecipeProduct extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('ms_recipe_product', 'recipe_id');
    }

    /**
     * @param $id
     * @return $this
     * @throws LocalizedException
     */
    public function clearAssignedProduct($id)
    {
        $this->getConnection()->delete($this->getMainTable(), ['recipe_id = ?' => $id]);

        return $this;
    }

    /**
     * @param $data
     * @return $this
     * @throws LocalizedException
     */
    public function saveAssignedProduct($data)
    {
        $this->getConnection()->insertMultiple($this->getMainTable(), $data);

        return $this;
    }

    /**
     * @param Collection $collection
     * @return Collection
     * @throws LocalizedException
     */
    public function addRecipeCountToCollection($collection)
    {
        $collection->getSelect()
            ->join(
                ['m2ms_recipe_product' => $this->getMainTable()],
                'e.entity_id = m2ms_recipe_product.product_id',
                ['recipes_count' => new \Zend_Db_Expr('count(1)')]
            )
            ->group('e.entity_id');

        return $collection;
    }
}
