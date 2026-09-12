<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Review\Product;

class Collection extends \Magento\Review\Model\ResourceModel\Review\Product\Collection
{
    protected function _joinFields()
    {
        $reviewTable = $this->_resource->getTableName('review');
        $reviewDetailTable = $this->_resource->getTableName('review_detail');
        $reviewEntityTable = $this->_resource->getTableName('review_entity');

        $this->addAttributeToSelect('name')->addAttributeToSelect('sku');

        $inCond = $this->getConnection()
            ->prepareSqlCondition(
                'ret.entity_code',
                ['neq' => \Mageside\Recipe\Model\Review::RECIPE_CODE]
            );

        $this->getSelect()->join(
            ['rt' => $reviewTable],
            'rt.entity_pk_value = e.entity_id',
            ['rt.review_id', 'review_created_at' => 'rt.created_at', 'rt.entity_pk_value', 'rt.status_id']
        )->join(
            ['rdt' => $reviewDetailTable],
            'rdt.review_id = rt.review_id',
            ['rdt.title', 'rdt.nickname', 'rdt.detail', 'rdt.customer_id', 'rdt.store_id']
        )->join(
            ['ret' => $reviewEntityTable],
            'ret.entity_id = rt.entity_id AND ' . $inCond,
            null
        );
        return $this;
    }
}
