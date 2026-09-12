<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel;

/**
 * Class CommentPublishPermissionPlugin
 */
class CommentPublishPermissionPlugin extends PublishPermissionPlugin
{
    /**
     * Check if action is allowed
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @param $object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave(
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource,
        $object
    ) {

        $this->processor->execute(
            $resource,
            $object,
            'Magefan_BlogPlus::comment_save_published',
            'status'
        );
    }
}
