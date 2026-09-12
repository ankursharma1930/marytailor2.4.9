<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\AmpEmail\Setup;

/**
 * Class Uninstall
 * @codingStandardsIgnoreFile
 * @package Plumrocket\AmpEmail\Setup
 */
class Uninstall extends \Plumrocket\Base\Setup\AbstractUninstall
{
    /**
     * Config section id
     *
     * @var string
     */
    protected $_configSectionId = 'prampemail';

    /**
     * Attributes
     *
     * @var array
     */
    protected $_attributes = [];

    /**
     * Tables
     *
     * @var array
     */
    protected $_tables = [
        \Plumrocket\AmpEmail\Model\ResourceModel\Security\VerifiedSender::MAIN_TABLE_NAME,
    ];

    /**
     * Tables Fields
     *
     * @var array
     */
    protected $_tablesFields = [
        'email_template' => [
            'pramp_email_enable',
            'pramp_email_content',
            'pramp_email_styles',
            'pramp_email_mode',
            'pramp_email_testing_method',
            'pramp_email_automatic_emails',
            'pramp_email_manual_email',
            'pramp_email_manual_order',
            'pramp_email_manual_send',
        ]
    ];

    /**
     * Pathes to files
     *
     * @var array
     */
    protected $_pathes = ['/app/code/Plumrocket/AmpEmail'];
}
