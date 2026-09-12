<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Recipe;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 * @package Mageside\Recipe\Controller\Adminhtml\Recipe
 */
class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $_filter;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * MassDelete constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Mageside\Recipe\Model\ResourceModel\Recipe\CollectionFactory $collectionFactory
    ) {
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->_collectionFactory->create();
        $collection->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        if (isset($this->getRequest()->getParams()['selected'])) {
            $collection->getCollectionById($this->getRequest()->getParams()['selected']);
        } elseif (isset($this->getRequest()->getParams()['excluded'])) {
            $collection->getCollectionByExcludedId($this->getRequest()->getParams()['excluded']);
        }

        $collectionSize = $collection->getSize();

        if ($collectionSize>0) {
            foreach ($collection as $block) {
                $block->delete();
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $collectionSize));
        } else $this->messageManager->addErrorMessage(__('A total of %1 record(s) have NOT been deleted.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('recipe/recipe/manage/');
    }
}
