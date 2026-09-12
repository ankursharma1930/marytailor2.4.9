<?php

namespace Dolphin\Productfaq\Controller\Adminhtml\Productfaq;

use Dolphin\Productfaq\Controller\Adminhtml\Productfaq;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultInterface;

class Edit extends Productfaq
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Edit action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('productfaq_id');
        $model = $this->_objectManager->create(\Dolphin\Productfaq\Model\Productfaq::class);

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Question no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->coreRegistry->register('dolphin_productfaq_productfaq', $model);

        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Question') : __('New Question'),
            $id ? __('Edit Question') : __('New Question')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Question'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? __('Edit Question', $model->getId()) : __('New Question'));
            
        return $resultPage;
    }
}
