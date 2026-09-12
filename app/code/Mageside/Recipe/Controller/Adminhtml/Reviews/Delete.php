<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Reviews;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $reviewFactory;

    /**
     * MassDelete constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Review\Model\ReviewFactory $reviewFactory
    ) {
        $this->reviewFactory = $reviewFactory;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $reviewId = $this->getRequest()->getParam('id');

        try {
            $model = $this->reviewFactory->create()->load($reviewId);
            $model->delete();
            $this->messageManager->addSuccessMessage(__('1 record have been deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting these records.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('recipe/reviews/manage/');

        return $resultRedirect;
    }
}
