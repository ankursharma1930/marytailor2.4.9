<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Plugin\Magento\Email\Model\ResourceModel;

use Magento\Framework\Api\SimpleDataObjectConverter;

/**
 * Save custom fields with started with "pramp_email_" string
 *
 * @package Plumrocket\AmpEmail\Plugin\Magento\Email\Model
 */
class TemplatePlugin
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * BackendTemplatePlugin constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @param \Magento\Email\Model\ResourceModel\Template $subject
     * @paarm $backendTemplate
     * @return object
     */
    public function beforeSave(
        \Magento\Email\Model\ResourceModel\Template $subject,
        $backendTemplate
    ) {
        if ('adminhtml_email_template_save' === $this->request->getFullActionName()) {
            foreach ($this->request->getParams() as $key => $value) {
                if (0 === strpos($key, 'pramp_email_')) {
                    /**
                     * Create setter name for compatibility with plugins functionality
                     */
                    $methodName = 'set' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($key);
                    $backendTemplate->$methodName($value);
                }
            }
        }

        return [$backendTemplate];
    }
}
