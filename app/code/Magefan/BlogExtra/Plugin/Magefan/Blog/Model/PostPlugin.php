<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Model;

use Magefan\Blog\Model\Post;

class PostPlugin
{
    /**
     * @param Post $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsVisibleOnStore(Post $subject, bool $result) : bool
    {
        return $result && ($subject->getData('end_time') >= $subject->getResource()->getDate()->gmtDate() ||
                !$subject->getData('end_time'));
    }
}
