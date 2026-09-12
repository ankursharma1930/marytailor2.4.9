<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Model;

/**
 * Magefan Blog Config Model
 */
class Config extends \Magefan\Blog\Model\Config
{
    const XML_AUTHOR_BLOCK_ENABLED = 'mfblog/author/author_block_enabled';
    const XML_LATEST_POSTS_BY_AUTHOR_ENABLED = 'mfblog/post_view/latest_posts_by_author/enabled';
    const XML_LATEST_POSTS_BY_AUTHOR_NUMBER = 'mfblog/post_view/latest_posts_by_author/number_of_posts';
}
