<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Block\Adminhtml\Author;

/**
 * Class DeleteButton
 */
class DeleteButton extends \Magefan\Community\Block\Adminhtml\Edit\DeleteButton
{
    /**
     * @return array|string
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed("Magefan_Blog::author_delete")) {
            return [];
        }
        return parent::getButtonData();
    }
}
