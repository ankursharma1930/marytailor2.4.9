<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Controller\Adminhtml;

/**
 * Admin blog extra comment subscriber edit controller
 */
class CommentSubscriber extends Actions
{
    /**
     * Form session key
     * @var string
     */
    protected $_formSessionKey = 'blogextra_commentsubscriber_form_data';

    /**
     * Allowed Key
     * @var string
     */
    protected $_allowedKey = 'Magefan_BlogExtra::comment_subscriber';

    /**
     * Model class name
     * @var string
     */
    protected $_modelClass = 'Magefan\BlogExtra\Model\CommentSubscriber';

    /**
     * Active menu key
     * @var string
     */
    protected $_activeMenu = 'Magefan_BlogExtra::comment_subscriber';

    /**
     * Status field name
     * @var string
     */
    protected $_statusField = 'status';
}
