<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe;

class Widget extends \Mageside\Recipe\Block\Frontend\Recipe\RecipeList
{
    /**
     * Recipe Featured values
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    /**
     * @return \Mageside\Recipe\Model\ResourceModel\Recipe\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAvailableRecipeCollection()
    {
        if (!$this->collection) {
            /** @var \Mageside\Recipe\Model\ResourceModel\Recipe\Collection $recipes */
            $recipes = $this->recipeCollectionFactory->create()->joinRecipeData();
            $recipeSize = $this->getData('recipe_size');
            switch ($this->getData('recipe_type')) {
                case 'featured':
                    $recipes
                        ->addStoreFilter()
                        ->addIsEnableFilter()
                        ->addFieldToFilter('featured', self::STATUS_ENABLED)
                        ->addOrder('recipe_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                        ->setPageSize($recipeSize ? $recipeSize : $this->helper->getRecipesPerPage());
                    break;
                case 'recently':
                    $recipes
                        ->addStoreFilter()
                        ->addIsEnableFilter()
                        ->addOrder('recipe_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                        ->setPageSize($recipeSize ? $recipeSize : $this->helper->getRecipesPerPage());
                    break;
            }
            $this->collection = $recipes;
        }

        return $this->collection;
    }
}
