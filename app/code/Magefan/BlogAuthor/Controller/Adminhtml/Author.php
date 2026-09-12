<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Controller\Adminhtml;

use Magefan\Blog\Controller\Adminhtml\Actions;

/**
 * Admin blog author edit controller
 */
class Author extends Actions
{
    /**
     * Form session key
     * @var string
     */
    protected $_formSessionKey  = 'blogauthor_author_form_data';

    /**
     * Allowed Key
     * @var string
     */
    protected $_allowedKey      = 'Magefan_Blog::author';

    /**
     * Model class name
     * @var string
     */
    protected $_modelClass      = 'Magefan\BlogAuthor\Model\Author';

    /**
     * Active menu key
     * @var string
     */
    protected $_activeMenu      = 'Magefan_Blog::author';

    /**
     * Status field name
     * @var string
     */
    protected $_statusField     = 'is_active';
}
