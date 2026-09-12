<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Image;

use Magento\Backend\App\Action;

class Upload extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    /**
     * @param Action\Context $context
     */
    protected $_fileUploader;

    /**
     * Upload constructor.
     * @param Action\Context $context
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     */
    public function __construct(
        Action\Context $context,
        \Mageside\Recipe\Model\FileUploader $fileUploader
    ) {
        $this->_fileUploader = $fileUploader;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($fileId = $this->getRequest()->getParam('file')) {
            try {
                $result = $this->_fileUploader->saveFileToDir($fileId);
                $result['cookie'] = [
                    'name' => $this->_getSession()->getName(),
                    'value' => $this->_getSession()->getSessionId(),
                    'lifetime' => $this->_getSession()->getCookieLifetime(),
                    'path' => $this->_getSession()->getCookiePath(),
                    'domain' => $this->_getSession()->getCookieDomain(),
                ];
            } catch (\Exception $e) {
                $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
            }
        } else {
            $result = ['error' => 'FILE ID NOT FOUND'];
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($result);
    }
}
