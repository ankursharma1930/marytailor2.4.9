<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Reviews;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Review model factory
     *
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\StoreFactory
     */
    protected $_recipeStoreFactory;

    /**
     * Edit constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Mageside\Recipe\Model\ResourceModel\StoreFactory $recipeStoreFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Framework\Registry $registry,
        \Mageside\Recipe\Model\ResourceModel\StoreFactory $recipeStoreFactory
    ) {
        $this->_reviewFactory = $reviewFactory;
        $this->_coreRegistry = $registry;
        $this->_recipeStoreFactory = $recipeStoreFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $this->initReviewData();

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Mageside_Recipe::recipe');
        $resultPage->getConfig()->getTitle()->prepend(__('Edit Review'));

        return $resultPage;
    }

    /**
     * Init review data for review form
     * @return void
     */
    public function initReviewData()
    {
        if ($this->getRequest()->getParam('review_id')) {
            $reviewData = $this->_reviewFactory->create()->load($this->getRequest()->getParam('review_id'));
            $this->_coreRegistry->register('review_data', $reviewData);
        } elseif ($recipeId = $this->getRequest()->getParam('recipe_id')) {
            $recipeStores = $this->_recipeStoreFactory->create()->getRecipeStores($recipeId);

            $reviewData = $this->_reviewFactory->create();
            $reviewData->setEntityPkValue($recipeId)
                ->setStores($recipeStores);
            $this->_coreRegistry->register('new_review_data', $reviewData);
        }
    }
}
