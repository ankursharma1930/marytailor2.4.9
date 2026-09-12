<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Cron;

use Magefan\Blog\Model\ResourceModel\Comment\CollectionFactory as CommentCollectionFactory;
use Magefan\BlogExtra\Model\CommentSubscriber\SendMail;
use Magefan\BlogExtra\Model\ResourceModel\CommentSubscriber\CollectionFactory as CommentSubscriberCollectionFactory;
use Magefan\BlogExtra\Model\CommentSubscriber as CommentSubscriberStatus;

/**
 * Class Send Comment Subscribe Email
 */
class SendCommentSubscribeEmail
{
    /**
     * @var CommentCollectionFactory
     */
    protected $commentCollectionFactory;

    /**
     * @var SendMail
     */
    protected $sender;

    /**
     * @var CommentSubscriberCollectionFactory
     */
    protected $commentSubscriberCollectionFactory;

    /**
     * @param CommentCollectionFactory $commentCollectionFactory
     * @param SendMail $sender
     * @param CommentSubscriberCollectionFactory $commentSubscriberCollectionFactory
     */
    public function __construct(
        CommentCollectionFactory $commentCollectionFactory,
        SendMail $sender,
        CommentSubscriberCollectionFactory $commentSubscriberCollectionFactory
    ) {
        $this->commentCollectionFactory = $commentCollectionFactory;
        $this->sender = $sender;
        $this->commentSubscriberCollectionFactory = $commentSubscriberCollectionFactory;
    }

    /**
     * Method which called in cron job
     */
    public function execute()
    {
        // Get comment collection
        $commentCollection = $this->commentCollectionFactory
            ->create()
            ->addFieldToFilter('notification', CommentSubscriberStatus::NEED_TO_SEND)
            ->addFieldToFilter('notification_sent', CommentSubscriberStatus::NOT_SENT)
            ->addFieldToFilter('status', \Magefan\Blog\Model\Config\Source\CommentStatus::APPROVED)
            ->addFieldToFilter('creation_time', ['gteq' => date('Y-m-d H:i:s', time() - 30 * 86400)])
            ->setOrder('creation_time', 'DESC');
        
        $done = [];

        foreach ($commentCollection as $lastComment) {
            $key = $lastComment->getPostId();

            if (empty($done[$key])) {
                $done[$key] = true;
                $authorEmail = $lastComment->getData('author_email');

                // Get comment subscriber collection
                $commentSubscriberCollection = $this->commentSubscriberCollectionFactory
                    ->create()
                    ->addFieldToFilter('status', CommentSubscriberStatus::SUBSCRIBED)
                    ->addFieldToFilter('post_id', $lastComment->getPostId());
                
                foreach ($commentSubscriberCollection as $subscriber) {
                    $subscriberEmail = $subscriber->getData('email');

                    if ($authorEmail != $subscriberEmail) {
                        $subscriber->setStoreId($lastComment->getStoreId());

                        // Send notification
                        $this->sender->sendEmail($subscriber, $lastComment);
                    }
                }
            }

            /* Mark as sent */
            $resource = $lastComment->getResource();
            $resource->getConnection()->update(
                $resource->getMainTable(),
                [
                    'notification_sent' => CommentSubscriberStatus::SENT,
                    'notification' => CommentSubscriberStatus::NOT_NEED_TO_SEND
                ],
                ['comment_id = ?' => $lastComment->getId()]
            );
        }
    }
}
