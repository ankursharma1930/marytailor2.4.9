<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Recipe;

use Magento\Store\Model\StoreManagerInterface;
use phpDocumentor\Reflection\Types\Null_;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    /**
     * @var \Mageside\Recipe\Model\RecipeFactory
     */
    protected $_recipeFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /** @var \Magento\Framework\Registry  */
    public $registry;

    /** @var \Mageside\Recipe\Helper\Config */
    public $helper;

    /**
     * Save constructor.
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mageside\Recipe\Model\RecipeFactory $recipeFactory
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Mageside\Recipe\Helper\Config $helper,
        \Magento\Backend\App\Action\Context $context,
        \Mageside\Recipe\Model\RecipeFactory $recipeFactory
    ) {
        $this->registry = $registry;
        $this->_recipeFactory     = $recipeFactory;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid Form Key. Please refresh the page.'));

            return $resultRedirect->setPath('*/*/manage');
        }

        $requestData = $this->getRequest()->getPostValue();
        /** @var \Mageside\Recipe\Model\Recipe $model */
        $model = $this->_recipeFactory->create();

        if (!$storeId = $this->_request->getParam('store')) {
            $storeId = 0;
        }
        $model->setStoreId($storeId);

        try {
            if (isset($requestData['recipe'])) {
                $model->addData($requestData['recipe']);
                $model->save();
                $model->saveRecipeData($requestData);

                $assignedProducts = !empty($requestData['related']) ? $requestData['related'] : [];
                $model->getResource()->saveAssignedProduct($model, $assignedProducts);
                $this->messageManager->addSuccessMessage(__('You saved the recipe.'));
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving recipe.'));
        }

        if (isset($requestData['back']) && $model->getId()) {
            return $resultRedirect->setPath('*/*/edit', ['recipe_id' => $model->getId()]);
        } else {
            return $resultRedirect->setPath('*/*/manage');
        }
    }
}
