<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe\Review;

use Magento\Customer\Model\Context;
use Magento\Customer\Model\Url;
use Magento\Review\Model\ResourceModel\Rating\Collection as RatingCollection;

class Form extends \Mageside\Recipe\Block\Frontend\Recipe\Review
{
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    /**
     * @var \Mageside\Recipe\Helper\Config
     */
    protected $config;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Customer\Model\Url $customerUrl,
        \Mageside\Recipe\Model\ResourceModel\SummaryFactory $summaryFactory,
        \Mageside\Recipe\Model\RecipeFactory $recipeModelF,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Mageside\Recipe\Helper\Config $config,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    ) {
        $this->urlEncoder = $urlEncoder;
        $this->config = $config;
        $this->httpContext = $httpContext;
        parent::__construct(
            $context,
            $collectionFactory,
            $coreRegistry,
            $ratingFactory,
            $customerUrl,
            $summaryFactory,
            $recipeModelF,
            $reviewFactory,
            $data
        );
    }

    protected function _construct()
    {
        parent::_construct();

        $this->setAllowWriteReviewFlag(
            $this->httpContext->getValue(Context::CONTEXT_AUTH)
            || $this->config->getIsGuestAllowToWrite()
        );
        if (!$this->getAllowWriteReviewFlag()) {
            $queryParam = $this->urlEncoder->encode(
                $this->getUrl('*/*/*', ['_current' => true]) . '#review-form'
            );
            $this->setLoginLink(
                $this->getUrl(
                    'customer/account/login/',
                    [Url::REFERER_QUERY_PARAM_NAME => $queryParam]
                )
            );
        }
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        return \Laminas\Json\Json::encode($this->_jsLayout);
    }

    /**
     * @return bool|\Mageside\Recipe\Model\Recipe
     */
    public function getProductInfo()
    {
        $recipeInfo = $this->getRecipe();
        $recipeInfo->setName($recipeInfo->getTitle());

        return $recipeInfo;
    }

    /**
     * Get review product post action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->getUrl(
            'recipe/review/post',
            [
                '_secure' => $this->getRequest()->isSecure(),
                'id' => $this->getRecipeId()
            ]
        );
    }

    /**
     * Get collection of ratings
     *
     * @return RatingCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRatings()
    {
        return $this->_ratingFactory->create()->getResourceCollection()->addEntityFilter(
            'recipe'
        )->setPositionOrder()->addRatingPerStoreName(
            $this->_storeManager->getStore()->getId()
        )->setStoreFilter(
            $this->_storeManager->getStore()->getId()
        )->setActiveFilter(
            true
        )->load()->addOptionToItems();
    }

    /**
     * Return register URL
     *
     * @return string
     */
    public function getRegisterUrl()
    {
        return $this->_customerUrl->getRegisterUrl();
    }
}
