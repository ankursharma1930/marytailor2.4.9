<?php

namespace Mageside\Recipe\Controller\Adminhtml\Filter;

use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Filter
     */
    private $filterResource;

    /**
     * MassDelete constructor.
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\Filter $filterResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Mageside\Recipe\Model\ResourceModel\Recipe\Filter $filterResource,
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->filterResource = $filterResource;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        $filterDeleted = 0;
        foreach ($collection as $item) {
            $this->filterResource->delete($item);
            $filterDeleted++;
        }

        if ($filterDeleted) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $filterDeleted)
            );
        }


        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('recipe/recipe/filter');
    }
}
