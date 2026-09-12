<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Model\ResourceModel;

use Magefan\Blog\Model\ResourceModel\Comment;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magefan\BlogExtra\Model\ResourceModel\CommentSubscriber\CollectionFactory as CommentSubscriberCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magefan\BlogExtra\Model\CommentSubscriber as CommentSubscriberStatus;

/**
 * Class Comment Plugin
 */
class CommentPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CommentSubscriberCollectionFactory
     */
    protected $commentSubscriberCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CommentSubscriberCollectionFactory $commentSubscriberCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CommentSubscriberCollectionFactory $commentSubscriberCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->commentSubscriberCollectionFactory = $commentSubscriberCollectionFactory;
        $this->storeManager = $storeManager;
    }

    public function beforeSave(Comment $resourceModel, $subject)
    {
        $notificationEnabled = $this->scopeConfig->getValue(
            'mfblog/post_view/comments/enabled',
            ScopeInterface::SCOPE_STORE
        );

        if (null === $subject->getData('notification')) {
            $subject->setData('notification', CommentSubscriberStatus::NEED_TO_SEND);
        }

        if (null === $subject->getData('notification_sent')) {
            $subject->setData('notification_sent', CommentSubscriberStatus::NOT_SENT);
        }

        if (null === $subject->getData('store_id')) {
            $subject->setData('store_id', $this->storeManager->getStore()->getId());
        }

        if (!$notificationEnabled) {
            $subject
                ->setData('notification', CommentSubscriberStatus::NOT_NEED_TO_SEND)
                ->setData('notification_sent', CommentSubscriberStatus::SENT);
        }
    }

    /**
     * @param Comment $subject
     * @param $result
     * @return mixed $result
     */
    public function afterSave(Comment $resourceModel, $result, $subject)
    {
        /*
        if ($subject->getData('status') !=  \Magefan\Blog\Model\Config\Source\CommentStatus::APPROVED) {
            return $result;
        }
        */

        $notificationEnabled = $this->scopeConfig->getValue(
            'mfblog/post_view/comments/enabled',
            ScopeInterface::SCOPE_STORE
        );

        if ($notificationEnabled) {
            /*
            if (!$subject->getData('notification_sent') && $subject->getData('notification') != CommentSubscriberStatus::NEED_TO_SEND) {
            */
                $postId = $subject->getData('post_id');
                $authorEmail = $subject->getData('author_email');

                $subscriber = $this->commentSubscriberCollectionFactory
                    ->create()
                    ->addFieldToFilter('email', $authorEmail)
                    ->addFieldToFilter('post_id', $postId)
                    ->setPageSize(1)
                    ->getFirstItem();

            if (!$subscriber->getId()) {
                $subscriber->addData([
                    'post_id' => $postId,
                    'email' => $authorEmail,
                    'status' => CommentSubscriberStatus::SUBSCRIBED,
                ])
                ->save();
            }
                /*
                $subject
                    ->setData('notification', CommentSubscriberStatus::NEED_TO_SEND)
                    ->save();
            }*/
        }

        return $result;
    }
}
