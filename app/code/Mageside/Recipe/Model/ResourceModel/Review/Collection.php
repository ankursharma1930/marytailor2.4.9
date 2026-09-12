<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Review;

class Collection extends \Magento\Review\Model\ResourceModel\Review\Collection
{
    protected $_idFieldName = 'main_table.review_id';

    /**
     * @inheritDoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'review_id') {
            $field = 'main_table.review_id';
        }
        parent::addFieldToFilter($field, $condition);

        return $this;
    }
}
