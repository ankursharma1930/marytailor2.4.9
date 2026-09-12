<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Adminhtml\Reviews;

class Rating extends \Magento\Review\Block\Adminhtml\Rating\Detailed
{
    /**
     * Get Rating collection for review form
     * @return \Magento\Review\Model\ResourceModel\Rating\Collection
     */
    public function getRating()
    {
        if (!$this->getRatingCollection()) {
            if ($this->_coreRegistry->registry('review_data')) {
                $stores = $this->_coreRegistry->registry('review_data')->getStores();
                $stores = array_diff($stores, [0]);

                $ratingCollection = $this->_ratingsFactory->create()->addEntityFilter(
                    \Mageside\Recipe\Model\Review::RECIPE_CODE
                )->setStoreFilter(
                    $stores
                )->setActiveFilter(
                    true
                )->setPositionOrder()->load()->addOptionToItems();

                $this->_voteCollection = $this->_votesFactory->create()->setReviewFilter(
                    $this->getReviewId()
                )->addOptionInfo()->load()->addRatingOptions();
            } elseif ($this->_coreRegistry->registry('new_review_data')) {
                $stores = $this->_coreRegistry->registry('new_review_data')->getStores();
                $stores = array_diff($stores, [0]);

                $ratingCollection = $this->_ratingsFactory->create()->addEntityFilter(
                    \Mageside\Recipe\Model\Review::RECIPE_CODE
                )->setStoreFilter(
                    $stores
                )->setPositionOrder()->load()->addOptionToItems();
            }

            $this->setRatingCollection($ratingCollection->getSize() ? $ratingCollection : false);
        }

        return $this->getRatingCollection();
    }

    /**
     * Produce and return block's html output
     * @return string
     */
    public function toHtml()
    {
        return '<fieldset class="admin__fieldset field-detailed-rating">
                    <div class="admin__field">
                        <label class="admin__field-label"><span>' . __('Rating') . '</span></label>
                        <div class="admin__field-control control">
                            <div id="detailed-rating" class="control-value admin__field-value">
                                <div id="rating_detail" class="recipe_rating_detail">' . parent::toHtml() . '</div>
                            </div>
                        </div>
                    </div>
                </fieldset>';
    }
}
