<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogPlus\Model;

/**
 * Class RelatedPost
 */
class RelatedPost extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Magefan\BlogPlus\Model\ResourceModel\RelatedPost::class);
    }
}
