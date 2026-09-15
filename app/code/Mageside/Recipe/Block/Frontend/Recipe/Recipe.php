<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe;

class Recipe extends \Mageside\Recipe\Block\Frontend\AbstractBlock
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Mageside\Recipe\Model\Recipe
     */
    protected $_recipe;

    protected $_collection;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe
     */
    protected $_recipeResourceModel;

    /**
     * @var \Mageside\Recipe\Model\RecipeFactory
     */
    protected $_recipeModelFactory;

    /**
     * @var \Mageside\Recipe\Model\WriterFactory
     */
    protected $_writerFactory;

    /**
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    protected $_reviewsColFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customer;

    /**
     * Recipe constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     * @param \Mageside\Recipe\Helper\Config $helper
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe $recipeResourceModel
     * @param \Mageside\Recipe\Model\RecipeFactory $recipeModelF
     * @param \Mageside\Recipe\Model\WriterFactory $writerFactory
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customer
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Mageside\Recipe\Model\FileUploader $fileUploader,
        \Mageside\Recipe\Helper\Config $helper,
        \Mageside\Recipe\Model\ResourceModel\Recipe $recipeResourceModel,
        \Mageside\Recipe\Model\RecipeFactory $recipeModelF,
        \Mageside\Recipe\Model\WriterFactory $writerFactory,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $collectionFactory,
        \Magento\Customer\Model\CustomerFactory $customer,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_recipeResourceModel = $recipeResourceModel;
        $this->_recipeModelFactory = $recipeModelF;
        $this->_writerFactory = $writerFactory;
        $this->_reviewsColFactory = $collectionFactory;
        $this->_customer = $customer;
        parent::__construct($context, $fileUploader, $helper);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
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
                'recipe_list',
                ['label' => __($this->helper->getSeoTitle()), 'link' => $this->helper->getRecipeListUrl()]
            )->addCrumb(
                'recipe_writer',
                [
                    'label' => __($this->getWriterName()),
                    'link' => $this->helper->getWriterUrl($this->getWriter())
                ]
            )->addCrumb(
                'recipe',
                ['label' => __($this->getRecipe()->getTitle())]
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * @return \Mageside\Recipe\Model\Recipe|mixed
     */
    public function getRecipe()
    {
        $recipe = $this->_coreRegistry->registry('recipe');
        if ($this->_recipe === null && $recipe) {
            return $recipe;
        } else {
            return $this->_recipeModelFactory->create();
        }
    }

    /**
     * @return mixed|null
     */
    public function getRecipeId()
    {
        $recipe = $this->getRecipe();
        return $recipe ? $recipe->getId() : null;
    }

    /**
     * @return \Mageside\Recipe\Model\Writer
     */
    public function getWriter()
    {
        $recipe = $this->getRecipe();
        $customerId = $recipe->getCustomerId();
        $writer = $this->_writerFactory->create();
        $writer->load($customerId, 'customer_id');

        if ($writer) {
            return $writer;
        } else {
            return $this->_writerFactory->create();
        }
    }

    /**
     * @return string
     */
    public function getWriterAvatar()
    {
        return $this->getImageUrl() . DIRECTORY_SEPARATOR . $this->getWriter()->getAvatar();
    }

    /**
     * @return string
     */
    protected function getWriterName() {
        $writer = $this->_customer->create()
            ->load($this->getRecipe()->getCustomerId());

        return $writer->getFirstname() . ' ' . $writer->getLastname();
    }


    /**------------------------------------**/

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    public function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * Prepares block data
     *
     * @return Recipe
     */
    public function prepareBlockData()
    {
        $this->addData(
            [
                'view_order_url' => $this->getUrl(
                    'recipe/recipe/view/',
                    ['recipe_id' => $this->getRecipeId()]
                ),
                'print_url' => $this->getUrl(
                    'recipe/recipe/printView',
                    ['id' => (int)$this->getRecipeId()]
                ),
                'recipe_id'  => (int)$this->getRecipeId()
            ]
        );
        return $this;
    }
}
