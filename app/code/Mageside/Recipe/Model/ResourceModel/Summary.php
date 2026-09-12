<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\ResourceModel;

/**
 * Class Summary
 * @package Mageside\Recipe\Model\ResourceModel
 */
class Summary extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('review_entity_summary', 'primary_id');
    }

    /**
     * @param $entityPkValue
     * @param $entityId
     * @param $storeId
     * @return string
     */
    public function getSummaryByEntityId($entityPkValue, $entityId, $storeId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                $this->getTable('review_entity_summary'),
                ['rating_summary']
            )
            ->where(
                'entity_pk_value = :pk_value 
                AND entity_type = :entity_type
                AND store_id = :store_id'
            );

        $bind = [':pk_value' => $entityPkValue, ':entity_type' => $entityId, ':store_id' => $storeId ];

        return $connection->fetchOne($select, $bind);
    }
}
