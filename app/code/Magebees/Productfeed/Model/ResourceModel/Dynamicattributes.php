<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Model\ResourceModel;

class Dynamicattributes extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magebees_productfeed_dynamicattributes', 'id');
    }
    public function getAllCustomAttributes()
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
          ->from($this->getMainTable());
        return $adapter->fetchAll($select);
    }
    public function getConditionByCode($attr_code)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from($this->getMainTable(), 'codeconditions')
            ->where('code = :code');
        $binds = ['code' => $attr_code];
        $conditions = $adapter->fetchOne($select, $binds);

        return json_decode($conditions, true);
    }
}
