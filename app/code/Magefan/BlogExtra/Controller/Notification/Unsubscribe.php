<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Controller\Notification;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magefan\BlogExtra\Model\ResourceModel\CommentSubscriber\CollectionFactory as CommentSubscriberCollectionFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magefan\BlogExtra\Model\CommentSubscriber as CommentSubscriberStatus;
use Magento\Framework\Url\DecoderInterface;

/**
 * Class Unsubscribe
 */
class Unsubscribe extends Action
{
    /**
     * @var CommentSubscriberCollectionFactory
     */
    protected $commentSubscriberCollectionFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * @param Context $context
     * @param CommentSubscriberCollectionFactory $commentSubscriberCollectionFactory
     * @param ManagerInterface $messageManager
     * @param EncryptorInterface $encryptor
     * @param DecoderInterface $decoder
     */
    public function __construct(
        Context $context,
        CommentSubscriberCollectionFactory $commentSubscriberCollectionFactory,
        ManagerInterface $messageManager,
        EncryptorInterface $encryptor,
        DecoderInterface $decoder
    ) {
        $this->commentSubscriberCollectionFactory = $commentSubscriberCollectionFactory;
        $this->messageManager = $messageManager;
        $this->encryptor = $encryptor;
        $this->decoder = $decoder;
        parent::__construct($context);
    }

    /**
     * Method which unsubscribe user from comment
     */
    public function execute()
    {
        $postId = $this->getRequest()->getParam('post_id');
        $email = $this->getRequest()->getParam('email');
        $keyHash = $this->decoder->decode($this->getRequest()->getParam('key'));
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($this->encryptor->decrypt($keyHash) !== $postId . $email) {
            $this->messageManager->addErrorMessage(__('Invalid key.'));

            $resultRedirect->setUrl($this->_redirect->getRefererUrl());

            return $resultRedirect;
        }

        $collection = $this->commentSubscriberCollectionFactory->create()
            ->addFieldToFilter('email', $email);

        if ($postId) {
            $collection->addFieldToFilter('post_id', $postId);
        }

        foreach ($collection as $item) {
            $item->setData('status', CommentSubscriberStatus::UNSUBSCRIBED)
                ->save();
        }

        $this->messageManager->addSuccessMessage(__('You have been unsubscribed successfully!'));

        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
