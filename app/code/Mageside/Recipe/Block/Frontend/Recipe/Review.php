<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe;

class Review extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Review collection
     *
     * @var \Magento\Review\Model\ResourceModel\Review\Collection
     */
    protected $_reviewsCollection;

    /**
     * Review resource model
     *
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Rating model
     *
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var array|mixed
     */
    protected $_jsLayout;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $_customerUrl;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\SummaryFactory
     */
    protected $_summaryFactory;

    /**
     * @var \Mageside\Recipe\Model\RecipeFactory
     */
    protected $_recipeModelFactory;

    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * Review constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Review\Model\RatingFactory $ratingFactory
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Mageside\Recipe\Model\ResourceModel\SummaryFactory $summaryFactory
     * @param \Mageside\Recipe\Model\RecipeFactory $recipeModelF
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Customer\Model\Url $customerUrl,
        \Mageside\Recipe\Model\ResourceModel\SummaryFactory $summaryFactory,
        \Mageside\Recipe\Model\RecipeFactory $recipeModelF,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_collectionFactory = $collectionFactory;
        $this->_ratingFactory = $ratingFactory;
        $this->_customerUrl = $customerUrl;
        $this->_summaryFactory = $summaryFactory;
        $this->_recipeModelFactory = $recipeModelF;
        $this->_reviewFactory = $reviewFactory;
        $this->_jsLayout = isset($data['jsLayout']) ? $data['jsLayout'] : [];

        parent::__construct($context);
    }

    /**
     * @return \Mageside\Recipe\Model\Recipe|bool
     */
    public function getRecipe()
    {
        $recipe = $this->_coreRegistry->registry('recipe');
        if ($recipe) {
            return $recipe;
        } else {
            return $this->_recipeModelFactory->create();
        }
    }

    /**
     * @return int|null
     */
    public function getRecipeId()
    {
        $recipe = $this->getRecipe();
        return $recipe ? $recipe->getId() : null;
    }

    /**
     * Get size of reviews collection
     *
     * @return int
     */
    public function getReviewsSize()
    {
        return $this->getReviewsCollection()->getSize();
    }

    /**
     * @return $this|\Magento\Review\Model\ResourceModel\Review\Collection
     */
    public function getReviewsCollection()
    {
        if (null === $this->_reviewsCollection) {
            $this->_reviewsCollection = $this->_collectionFactory->create()
                ->addStatusFilter(\Magento\Review\Model\Review::STATUS_APPROVED)
                ->addEntityFilter(
                    \Mageside\Recipe\Model\Review::RECIPE_CODE,
                    (int)$this->getRecipeId()
                )
                ->setDateOrder();
        }

        return $this->_reviewsCollection;
    }

    /**
     * @return mixed
     */
    public function getRatingSummary()
    {
        return $this->getRecipe()->getRatingSummary();
    }
}
