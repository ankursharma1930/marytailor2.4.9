<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Review;

use Magento\Framework\Controller\ResultFactory;
use Magento\Review\Model\Review;

class Post extends \Magento\Framework\App\Action\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Customer session model
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Generic session
     *
     * @var \Magento\Framework\Session\Generic
     */
    protected $_reviewSession;

    /**
     * Catalog catgory model
     *
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $_categoryRepository;

    /**
     * Catalog product model
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Review model
     *
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * Rating model
     *
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $_ratingFactory;

    /**
     * Catalog design model
     *
     * @var \Magento\Catalog\Model\Design
     */
    protected $_catalogDesign;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Core form key validator
     *
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Mageside\Recipe\Model\RecipeFactory
     */
    protected $_recipeFactory;

    /**
     * PostReview constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Magento\Review\Model\RatingFactory $ratingFactory
     * @param \Magento\Catalog\Model\Design $catalogDesign
     * @param \Magento\Framework\Session\Generic $reviewSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Mageside\Recipe\Model\RecipeFactory $recipeFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Catalog\Model\Design $catalogDesign,
        \Magento\Framework\Session\Generic $reviewSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Mageside\Recipe\Model\RecipeFactory $recipeFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_coreRegistry = $coreRegistry;
        $this->_customerSession = $customerSession;
        $this->_reviewSession = $reviewSession;
        $this->_categoryRepository = $categoryRepository;
        $this->_productRepository = $productRepository;
        $this->_reviewFactory = $reviewFactory;
        $this->_ratingFactory = $ratingFactory;
        $this->_catalogDesign = $catalogDesign;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_recipeFactory = $recipeFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $data = $this->_reviewSession->getFormData(true);

        if ($data) {
            $rating = [];
            if (isset($data['ratings']) && is_array($data['ratings'])) {
                $rating = $data['ratings'];
            }
        } else {
            $data = $this->getRequest()->getParams();
            $rating = $this->getRequest()->getParam('ratings', []);
        }

        if ($recipeId = $this->getRequest()->getParam('id')) {

            $recipe = $this->_recipeFactory->create();
            $recipe->setStoreId($this->_storeManager->getStore()->getId());
            $recipe->load($recipeId);

            if ($recipe->getRecipeId() && ($recipe->isRecipeExistByStore($this->_storeManager->getStore()->getId(), $recipe->getRecipeId()))) {
                $this->_coreRegistry->register('recipe', $recipe);
            }
            if ($recipe->getId() && !empty($data)) {
                /** @var \Magento\Review\Model\Review $review */
                $review = $this->_reviewFactory->create()->setData($data);
                $review->unsetData('review_id');

                $validate = $review->validate();

                if ($validate === true) {
                    try {
                        $review
                            ->setEntityId(
                                $review->getEntityIdByCode(\Mageside\Recipe\Model\Review::RECIPE_CODE)
                            )
                            ->setEntityPkValue($recipe->getId())
                            ->setStatusId(Review::STATUS_PENDING)
                            ->setCustomerId($this->_customerSession->getCustomerId())
                            ->setStoreId($this->_storeManager->getStore()->getId())
                            ->setStores([$this->_storeManager->getStore()->getId()])
                            ->save();

                        foreach ($rating as $ratingId => $optionId) {
                            $this->_ratingFactory->create()
                                ->setRatingId($ratingId)
                                ->setReviewId($review->getId())
                                ->setCustomerId($this->_customerSession->getCustomerId())
                                ->addOptionVote($optionId, $recipe->getId());
                        }

                        $review->aggregate();
                        $this->messageManager->addSuccessMessage(__('You submitted your review for moderation.'));
                    } catch (\Exception $e) {
                        $this->_reviewSession->setFormData($data);
                        $this->messageManager->addErrorMessage(__('We can\'t post your review right now.'));
                    }
                } else {
                    $this->_reviewSession->setFormData($data);
                    if (is_array($validate)) {
                        foreach ($validate as $errorMessage) {
                            $this->messageManager->addErrorMessage($errorMessage);
                        }
                    } else {
                        $this->messageManager->addErrorMessage(__('We can\'t post your review right now.'));
                    }
                }
            }
        }

        $redirectUrl = $this->_reviewSession->getRedirectUrl(true);
        $resultRedirect->setUrl($redirectUrl ?: $this->_redirect->getRedirectUrl());

        return $resultRedirect;
    }
}
