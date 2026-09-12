<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe;

class GoogleRichSnippets extends \Mageside\Recipe\Block\Frontend\AbstractBlock
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var \Magento\Review\Model\Rating\Option\VoteFactory
     */
    protected $_ratingOptionVoteF;

    /**
     * @var \Mageside\Recipe\Model\WriterFactory
     */
    protected $_writerFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * GoogleRichSnippets constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Magento\Review\Model\Rating\Option\VoteFactory $ratingOptionVoteF
     * @param \Mageside\Recipe\Model\WriterFactory $writerFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     * @param \Mageside\Recipe\Helper\Config $helper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\Rating\Option\VoteFactory $ratingOptionVoteF,
        \Mageside\Recipe\Model\WriterFactory $writerFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Mageside\Recipe\Model\FileUploader $fileUploader,
        \Mageside\Recipe\Helper\Config $helper
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_reviewFactory = $reviewFactory;
        $this->_ratingOptionVoteF = $ratingOptionVoteF;
        $this->_writerFactory = $writerFactory;
        $this->_jsonHelper = $jsonHelper;
        parent::__construct($context, $fileUploader, $helper);
    }

    /**
     * @return mixed
     */
    public function getRecipe()
    {
        return $this->_coreRegistry->registry('recipe');
    }

    /**
     * @return string
     */
    protected function getWriterName()
    {
        $writer = $this->_writerFactory->create()
            ->load($this->getRecipe()->getCustomerId(), 'customer_id');

        if ($writer->getNickname()) {
            return $writer->getNickname();
        }

        return $writer->getFirstname() . ' ' . $writer->getLastname();
    }

    /**
     * @param $recipe
     * @return array
     */
    protected function getSummaryReviewData($recipe)
    {
        $review = $this->_reviewFactory->create();
        $entityTypeId = $review->getEntityIdByCode(\Mageside\Recipe\Model\Review::RECIPE_CODE);

        $reviewCollection = $review
            ->getCollection()
            ->addFieldToFilter('entity_pk_value', $recipe->getRecipeId())
            ->addFieldToFilter('entity_id', $entityTypeId);

        $countReviews = $reviewCollection->count();
        $summaryData = [];
        if ($countReviews > 0) {
            $percent = $this->getRecipe()->getRatingSummary();
            $summaryData = [
                'percent'   => $percent,
                'count'     => $countReviews,
            ];
        }

        return $summaryData;
    }

    /**
     * @return null|string
     */
    public function getJsonData()
    {
        $recipe = $this->getRecipe();

        if ($recipe) {
            $reviewData = $this->getSummaryReviewData($recipe);

            $result = [
                "@context" => "http://schema.org/",
                "@type" => "Recipe",
                "name" => $recipe->getTitle(),
                "image" => $this->fileUploader->getFileWebUrl($recipe->getThumbnail()),
                "description" => $recipe->getShortDescriptionHtml(),
            ];
            if ($recipe->getCustomerId()) {
                $result["author"] = [
                    "@type" => "Person",
                    "name"  => $this->getWriterName()
                ];
            }
            if (!empty($reviewData)) {
                $result["aggregateRating"] = [
                    "@type"=> "AggregateRating",
                    "ratingValue"=> $reviewData['percent'],
                    "reviewCount"=> $reviewData['count']
                ];
            }
            if ($recipe->getPrepTime()) {
                $result["prepTime"] = 'PT' . str_replace(':', $this->escapeHtml('H'), $recipe->getPrepTime()) . 'M';
            }
            if ($recipe->getCookTime()) {
                $result["cookTime"] = 'PT' . str_replace(':', $this->escapeHtml('H'), $recipe->getCookTime()) . 'M';
            }
            if ($recipe->getServingsNumber()) {
                $result["recipeYield"] = __('Serves') . ' '
                    . $this->getFormatServingsNumber($recipe->getServingsNumber());
            }
            if ($ingredients = $recipe->getIngredients()) {
                foreach ($ingredients as $ingredient) {
                    if (!empty($ingredient['measure'])) {
                        $result["recipeIngredient"][] = $ingredient['measure'] . ' ' . $ingredient['ingredient'];
                    } else {
                        $result["recipeIngredient"][] = $ingredient['ingredient'];
                    }
                }
            }
            if (!empty($recipe->getMethod())) {
                $instructions = '';
                $i = 0;
                foreach ($recipe->getMethod() as $methodData) {
                    if (!empty($methodData['step'])) {
                        $i++;
                        $instructions .= $i . '. ' . $methodData['step'] . ' ';
                    }
                }
                if (!empty($instructions)) {
                    $result["recipeInstructions"] = $instructions;
                }
            }

            return $this->_jsonHelper->jsonEncode($result);
        }

        return null;
    }
}
