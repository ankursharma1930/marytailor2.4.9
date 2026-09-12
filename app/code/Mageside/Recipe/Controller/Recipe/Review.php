<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Recipe;

use Magento\Store\Model\StoreManagerInterface;

class Review extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Mageside\Recipe\Model\RecipeFactory
     */
    protected $recipeFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Mageside\Recipe\Model\RecipeFactory $recipeFactory,
        \Magento\Framework\Registry $coreRegistry,
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
        $result = ['ingredients' => ''];

        $recipe = $this->recipeFactory->create();
        if ($recipeId = $this->getRequest()->getParam('recipe_id')) {
            $recipe->setStoreId($this->storeManager->getStore()->getId());
            $recipe->load($recipeId);
        }

        if ($recipe->getRecipeId()
            && ($recipe->isRecipeExistByStore($this->storeManager->getStore()->getId(), $recipe->getRecipeId()))
        ) {
            $this->coreRegistry->register('recipe', $recipe);

            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);

            /** @var \Mageside\Recipe\Block\Frontend\Recipe\ListProduct $contentBlock */
            if ($contentBlock = $resultPage->getLayout()->getBlock('ingredients_list')) {
                $content = $contentBlock->toHtml();
                $result = ['ingredients' => $content];
            }
        }

        return $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData($result);
    }
}
