<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe\Review;

class ListView extends \Mageside\Recipe\Block\Frontend\Recipe\Review
{
    /**
     * Prepare recipe review list toolbar
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $toolbar = $this->getLayout()->getBlock('recipe_review_list.toolbar');
        if ($toolbar) {
            $toolbar->setCollection($this->getReviewsCollection());
            $toolbar->setPath('recipe/review/listAjax/id/' . $this->getRecipeId());
            $this->setChild('toolbar', $toolbar);
        }

        return $this;
    }

    /**
     * Add rate votes
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->getReviewsCollection()->load()->addRateVotes();
        return parent::_beforeToHtml();
    }
}
