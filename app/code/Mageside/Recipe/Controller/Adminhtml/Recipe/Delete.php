<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Recipe;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Delete
 */
class Delete extends \Magento\Backend\App\Action
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
     * Delete constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mageside\Recipe\Model\RecipeFactory $recipeFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mageside\Recipe\Model\RecipeFactory $recipeFactory
    ) {
        $this->_recipeFactory = $recipeFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($recipeId = $this->getRequest()->getParam('id')) {
            try {
                $model = $this->_recipeFactory->create()->load($recipeId);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('1 record have been deleted.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting these records.'));
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/manage');

        return $resultRedirect;
    }
}
