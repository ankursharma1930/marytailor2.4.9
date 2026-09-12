<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Filter;

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
     * @var \Mageside\Recipe\Model\Recipe\FilterFactory
     */
    protected $_recipeFactory;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\FilterFactory
     */
    protected $_filterResourceFactory;

    /**
     * Delete constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mageside\Recipe\Model\Recipe\FilterFactory $recipeFactory
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\FilterFactory $filterResourceFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mageside\Recipe\Model\Recipe\FilterFactory $recipeFactory,
        \Mageside\Recipe\Model\ResourceModel\Recipe\FilterFactory $filterResourceFactory
    ) {
        $this->_recipeFactory = $recipeFactory;
        $this->_filterResourceFactory = $filterResourceFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        if ((bool)$id) {
            try {
                $model = $this->_recipeFactory->create();
                $resourceModel = $this->_filterResourceFactory->create()->load($model, $id);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This entity no longer exists.'));
                }
                $resourceModel->delete($model);
                $this->messageManager->addSuccessMessage(__('You deleted the entity.'));
            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage(__('Something went wrong while deleting the entity.'));
            }
        }

        return $resultRedirect->setPath('recipe/recipe/filter');
    }
}
