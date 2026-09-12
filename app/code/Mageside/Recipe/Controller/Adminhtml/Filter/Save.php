<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Filter;

use Magento\Store\Model\StoreManagerInterface;

class Save extends \Magento\Backend\App\Action
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
    protected $filterFactory;

    /**
     * Store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Save constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mageside\Recipe\Model\Recipe\FilterFactory $filterFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mageside\Recipe\Model\Recipe\FilterFactory $filterFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->filterFactory = $filterFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid filer data.'));

            return $resultRedirect->setPath('*/recipe/filter');
        }

        $requestData = $this->getRequest()->getPostValue();
        $model = $this->filterFactory->create();

        if (isset($requestData['store'])) {
            $requestData['filter']['store_id' ] = $requestData['store'];
        } else {
            $requestData['filter']['store_id' ] = 0;
        }
        try {
            if (isset($requestData['filter'])) {
                $model->addData($requestData['filter']);
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the filter.'));
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $resultRedirect->setPath('recipe/recipe/filter');
    }
}
