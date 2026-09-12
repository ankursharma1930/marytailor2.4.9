<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Recipe;

use Magento\Framework\Controller\ResultFactory;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Mageside\Recipe\Model\RecipeFactory
     */
    protected $recipeFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;

    /**
     * @var \Mageside\Recipe\Helper\Config
     */
    public $helper;

    /**
     * Edit constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Mageside\Recipe\Model\RecipeFactory $recipeFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Mageside\Recipe\Helper\Config $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Mageside\Recipe\Model\RecipeFactory $recipeFactory
    ) {
        $this->storeManager = $storeManager;
        $this->recipeFactory = $recipeFactory;
        $this->registry = $coreRegistry;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('recipe_id');
        $recipe = $this->recipeFactory->create();
        $currentStoreId = $this->getRequest()->getParam('store', 0);
        $recipe->setStoreId($currentStoreId);
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        if ((bool)$id) {
            $recipe->load($id);
        }
        $recipeName = $recipe->getTitle();

        if ($id = $recipe->getId()) {
            $this->registry->register('current_recipe', $recipe);
            $resultPage->getConfig()->getTitle()->prepend(__('Edit Recipe: %1', $recipeName));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Recipe'));
        }
        $resultPage->setActiveMenu('Mageside_Recipe::recipe');

        return $resultPage;
    }
}
