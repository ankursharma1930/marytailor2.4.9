<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe;

use Magento\Catalog\Api\CategoryRepositoryInterface;

/**
 * Class ListProduct
 * @package Mageside\Recipe\Block\Frontend\Recipe
 */
class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * ListProduct constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        array $data = []
    ) {
        $this->registry = $context->getRegistry();
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    /**
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getProductCollection()
    {
        if ($this->_productCollection === null) {
            $this->_productCollection = $this->getCurrentRecipe()->getAssignedProducts();
        }

        return $this->_productCollection;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentRecipe()
    {
        $recipe = $this->getData('recipe');
        if ($recipe === null) {
            $recipe = $this->registry->registry('recipe');
            if ($recipe) {
                $this->setData('recipe', $recipe);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Recipe not found.')
                );
            }
        }

        return $recipe;
    }
}
