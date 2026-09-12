<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email;

class EmailAddressParser implements EmailAddressParserInterface
{
    /**
     * @var \Magento\Framework\Validator\EmailAddress
     */
    private $emailValidator;

    public function __construct(\Magento\Framework\Validator\EmailAddress $emailValidator)
    {
        $this->emailValidator = $emailValidator;
    }

    /**
     * @param string $string
     * @return array
     */
    public function getValidEmails(string $string) : array
    {
        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $string, $matches);

        $result = array_unique($matches[0]);

        $emailValidator = $this->emailValidator;

        $result = array_filter($result, static function ($email) use ($emailValidator) {
            return $emailValidator->isValid($email);
        });

        return $result;
    }
}
