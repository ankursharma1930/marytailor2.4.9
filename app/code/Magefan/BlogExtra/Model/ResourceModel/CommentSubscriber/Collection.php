<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Model\ResourceModel\CommentSubscriber;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Blog extra comment subscription resource model collection
 */
class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magefan\BlogExtra\Model\CommentSubscriber::class, \Magefan\BlogExtra\Model\ResourceModel\CommentSubscriber::class);
    }
}
