<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Cron\Facebook;

use Magefan\BlogPlus\Model\Facebook\Publisher;

/**
 * Class AutoPublishPost
 */
class AutoPublishPost
{
    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * AutoPublishPost constructor.
     * @param Publisher $publisher
     * @param CollectionFactory $postsCollectionFactory
     */
    public function __construct(
        Publisher $publisher
    ) {
        $this->publisher = $publisher;
    }

    /**
     * Method which called in cron job
     */
    public function execute()
    {
        $this->publisher->publishPosts();
    }
}
