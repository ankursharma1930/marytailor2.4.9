<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Reviews;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    /**
     * @var \Mageside\Recipe\Model\ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Save constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Magento\Review\Model\RatingFactory $ratingFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_reviewFactory = $reviewFactory;
        $this->_ratingFactory = $ratingFactory;
        $this->_storeManager = $storeManager;


        parent::__construct($context);
    }

    public function execute()
    {
        $recipeId = $this->getRequest()->getParam('recipe_id', false);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        if ($data = $this->getRequest()->getPostValue()) {
            /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
            $storeManager = $this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
            if ($storeManager->hasSingleStore()) {
                $data['stores'] = [
                    $storeManager->getStore(true)->getId(),
                ];
            } elseif (isset($data['select_stores'])) {
                $data['stores'] = $data['select_stores'];
            }
            $review = $this->_reviewFactory->create()->setData($data);
            try {
                $review->setEntityId($review->getEntityIdByCode(\Mageside\Recipe\Model\Review::RECIPE_CODE))
                    ->setEntityPkValue($recipeId)
                    ->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID)
                    ->setStatusId($data['status_id'])
                    ->setCustomerId(null)
                    ->save();

                $votes = $this->_objectManager->create(\Magento\Review\Model\Rating\Option\Vote::class)
                    ->getResourceCollection()
                    ->setReviewFilter($review->getId())
                    ->addOptionInfo()
                    ->load()
                    ->addRatingOptions();

                $arrRatingId = $this->getRequest()->getParam('ratings', []);
                foreach ($arrRatingId as $ratingId => $optionId) {
                    if ($vote = $votes->getItemByColumnValue('rating_id', $ratingId)) {
                        $this->_ratingFactory->create()
                            ->setVoteId($vote->getId())
                            ->setReviewId($review->getId())
                            ->updateOptionVote($optionId);
                    } else {
                        $this->_ratingFactory->create()
                            ->setRatingId($ratingId)
                            ->setReviewId($review->getId())
                            ->addOptionVote($optionId, $review->getEntityPkValue());
                    }
                }

                $review->aggregate();

                $this->messageManager->addSuccessMessage(__('The review has been saved.'));
                $resultRedirect->setPath('recipe/reviews/manage');

                return $resultRedirect;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving this review.'));
            }
        }
        $resultRedirect->setPath('recipe/reviews/manage');

        return $resultRedirect;
    }
}
