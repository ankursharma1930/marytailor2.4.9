<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\ResourceModel\Security;

class VerifiedSender extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Name of Main Table
     */
    const MAIN_TABLE_NAME = 'plumrocket_amp_email_sender';

    /**
     * Name of Primary Column
     */
    const MAIN_TABLE_ID_FIELD_NAME = 'email';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE_NAME, self::MAIN_TABLE_ID_FIELD_NAME);
    }
}
