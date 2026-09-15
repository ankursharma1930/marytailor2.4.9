<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Recipe;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Action\Context;
use Mageside\Recipe\Model\RecipeFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Registry;

class View extends Action
{
    /**
     * @var RecipeFactory
     */
    protected $recipeFactory;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * View constructor.
     * @param Context $context
     * @param RecipeFactory $recipeFactory
     * @param Registry $coreRegistry
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        RecipeFactory $recipeFactory,
        Registry $coreRegistry,
        StoreManagerInterface $storeManager
    ) {
        $this->recipeFactory = $recipeFactory;
        $this->coreRegistry = $coreRegistry;
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $recipe = $this->recipeFactory->create();
        if ($recipeId = $this->getRequest()->getParam('recipe_id')) {
            $recipe->setStoreId($this->storeManager->getStore()->getId());
            $recipe->load($recipeId);
        }

        if ($recipe->getRecipeId()
            && $recipe->getStatus()
            && ($recipe->isRecipeExistByStore($this->storeManager->getStore()->getId(), $recipe->getRecipeId()))
        ) {
            $this->coreRegistry->register('recipe', $recipe);
        } else {
            return $this->resultFactory
                ->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD)
                ->forward('noroute');
        }

        // Meta fields are NULL for recipes saved without them; trim(null) is deprecated
        // on PHP 8.1+ and Magento's error handler turns that into a 500
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        if (trim((string) $recipe->getMetaTitle())) {
            $resultPage->getConfig()->getTitle()->set(__(trim((string) $recipe->getMetaTitle())));
        } else {
            $resultPage->getConfig()->getTitle()->set(__(trim((string) $recipe->getTitle())));
        }
        $resultPage->getConfig()->setDescription(__(trim((string) $recipe->getMetaDescription())));
        $resultPage->getConfig()->setKeywords(__(trim((string) $recipe->getMetaKeywords())));

        return $resultPage;
    }
}
