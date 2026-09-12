<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Reviews;

use Magento\Framework\Controller\ResultFactory;

class MassStatus extends \Magento\Backend\App\Action
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
     * @var \Mageside\Recipe\Model\ResourceModel\Review\CollectionFactory
     */
    protected $_reviewCollectionFactory;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $_filter;

    /**
     * MassStatus constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Mageside\Recipe\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Mageside\Recipe\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory,
        \Magento\Review\Model\ReviewFactory $reviewFactory
    ) {
        $this->_filter = $filter;
        $this->reviewFactory = $reviewFactory;
        $this->_reviewCollectionFactory = $reviewCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $status = $this->getRequest()->getParam('status');
        $collection = $this->_filter->getCollection($this->_reviewCollectionFactory->create());

        foreach ($collection as $review) {
            $review->setStatusId($status);
            $review->setIdFieldName('review_id');
            $review->save();
            $review->aggregate();
        }

        $collectionSize = $collection->getSize();
        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been updated.', $collectionSize)
        );

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('recipe/reviews/manage/');
    }
}
