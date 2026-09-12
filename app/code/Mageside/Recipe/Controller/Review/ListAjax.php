<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Review;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;

class ListAjax extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Mageside\Recipe\Model\RecipeFactory
     */
    protected $_recipeFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /**
     * ListAjax constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Mageside\Recipe\Model\RecipeFactory $recipeFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Mageside\Recipe\Model\RecipeFactory $recipeFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_recipeFactory = $recipeFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Layout
     * @throws LocalizedException
     */
    public function execute()
    {
        if (!$this->initRecipe()) {
            throw new LocalizedException(__('Cannot initialize recipe.'));
        } else {
            /** @var \Magento\Framework\View\Result\Layout $resultLayout */
            $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        }

        return $resultLayout;
    }

    /**
     * @return \Mageside\Recipe\Model\Recipe|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function initRecipe()
    {
        $recipe = $this->_recipeFactory->create();
        if ($recipeId = $this->getRequest()->getParam('id')) {
            $recipe->setStoreId($this->storeManager->getStore()->getId());
            $recipe->load($recipeId);
        }

        if ($recipe->getRecipeId() && ($recipe->isRecipeExistByStore($this->storeManager->getStore()->getId(), $recipe->getRecipeId()))) {
            $this->_coreRegistry->register('recipe', $recipe);
            return $recipe;
        }

        return false;
    }
}
