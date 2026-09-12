<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogPlus\Model\ResourceModel\RelatedPost;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'post_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magefan\BlogPlus\Model\RelatedPost', 'Magefan\BlogPlus\Model\ResourceModel\RelatedPost');
    }
}
