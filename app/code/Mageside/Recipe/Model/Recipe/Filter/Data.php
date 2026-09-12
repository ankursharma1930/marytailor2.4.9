<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\Recipe\Filter;

class Data extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Data::class);
    }
}
