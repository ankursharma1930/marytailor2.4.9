<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\Layer;

use Magento\Catalog\Model\Layer\ItemCollectionProviderInterface;

class ItemCollectionRecipe implements ItemCollectionProviderInterface
{
    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getCollection(\Magento\Catalog\Model\Category $category)
    {
        return $category->getProductCollection();
    }
}
