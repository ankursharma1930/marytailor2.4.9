<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Plugin\Block\Post\View;

use Magefan\Blog\Block\Post\View\Comments;

/**
 * Class CommentsPlugin
 */
class CommentsPlugin
{
    /**
     * @param Comments $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundToHtml(Comments $subject, callable $proceed)
    {
        if (!$subject->getPost()->getEnableComments()) {
            return '';
        }

        return $proceed();
    }
}
