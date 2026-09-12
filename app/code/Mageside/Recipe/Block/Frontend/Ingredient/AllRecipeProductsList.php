<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Ingredient;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Page\Title;
use Mageside\Recipe\Model\ResourceModel\RecipeProduct;
use Mageside\Recipe\Helper\Config;

/**
 * Class AllRecipeProductsList
 * @package Mageside\Recipe\Block\Frontend\Ingredient
 */
class AllRecipeProductsList extends Template
{
    /** @var string */
    public $title;

    /** @var CollectionFactory */
    public $productCollectionFactory;

    /** @var RecipeProduct  */
    public $recipeProductResource;

    /** @var Registry */
    public $coreRegistry;

    /** @var $helper */
    public $helper;

    /**
     * AllRecipeProducts constructor.
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param RecipeProduct $recipeProductResource
     * @param Registry $coreRegistry
     * @param Config $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        RecipeProduct $recipeProductResource,
        Registry $coreRegistry,
        Config $helper,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->recipeProductResource = $recipeProductResource;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return Collection
     * @throws LocalizedException
     */
    public function getRecipeProductCollection()
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection
            ->addAttributeToSelect(['name'])
            ->addAttributeToSort('name')
            ->addUrlRewrite();

        $this->recipeProductResource->addRecipeCountToCollection($productCollection);

        return $productCollection;
    }

    /**
     * @param $id array
     * @return mixed
     */
    public function getRecipesUrl($id)
    {
        return $this->getUrl('recipe/ingredient/view',$id);
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setTitle(str_replace('{product_name}',$this->coreRegistry->registry('product_name'),$this->helper->getIngredientPageTitle()));
        $this->pageConfig->getTitle()->set(__($this->getTitle()));
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            )->addCrumb(
                'ingredient_list',
                [
                    'label' => __($this->helper->getRecipesPerIngredientPageTitle()), 'link' => $this->getUrl('recipe/ingredient')
                ]
            )->addCrumb(
                'recipe',
                [
                    'label' => __($this->getTitle())
                ]
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title
     * @return Title
     */
    public function setTitle($title)
    {
        return $this->title=$title;
    }
}
