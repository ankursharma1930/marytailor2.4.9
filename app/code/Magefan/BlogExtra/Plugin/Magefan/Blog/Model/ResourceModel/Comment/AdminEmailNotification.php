<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Model\ResourceModel\Comment;

use Magefan\Blog\Model\ResourceModel\Comment;
use Magefan\BlogExtra\Model\AdminEmailNotification\SendEmail;

/**
 * Class AdminEmailNotification
 */
class AdminEmailNotification
{

    /**
     * @var SendEmail
     */
    protected $sendEmail;

    /**
     * AdminEmailNotification constructor.
     * @param SendEmail $sendEmail
     */
    public function __construct(
        SendEmail $sendEmail
    ) {
        $this->sendEmail = $sendEmail;
    }

    /**
     * @param Comment $resourceModel
     * @param $result
     * @param $subject
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterSave(Comment $resourceModel, $result, $subject)
    {
        if ($subject->getStatus() == \Magefan\Blog\Model\Config\Source\CommentStatus::PENDING) {
            $this->sendEmail->sendEmail($subject);
        }
        return $result;
    }
}
