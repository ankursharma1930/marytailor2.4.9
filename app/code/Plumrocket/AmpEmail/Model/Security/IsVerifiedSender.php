<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Security;

class IsVerifiedSender implements \Plumrocket\AmpEmail\Api\IsVerifiedSenderInterface
{
    /**
     * @var string[]
     */
    private $testAmpSourceOrigins;

    /**
     * @var \Plumrocket\AmpEmail\Api\GetVerifiedSenderListInterface
     */
    private $getVerifiedSenderList;

    /**
     * IsVerifiedSender constructor.
     *
     * @param \Plumrocket\AmpEmail\Api\GetVerifiedSenderListInterface $getVerifiedSenderList
     * @param array                                                   $testAmpSourceOrigins
     */
    public function __construct(
        \Plumrocket\AmpEmail\Api\GetVerifiedSenderListInterface $getVerifiedSenderList,
        array $testAmpSourceOrigins = []
    ) {
        $this->getVerifiedSenderList = $getVerifiedSenderList;
        $this->testAmpSourceOrigins = array_keys(array_filter($testAmpSourceOrigins));
    }

    /**
     * @param string $email
     * @param bool   $allowRequestFromAmpPlayground
     * @return bool
     */
    public function execute(string $email, bool $allowRequestFromAmpPlayground = false) : bool
    {
        $allowedSenders = $this->getVerifiedSenderList->execute();
        if ($allowRequestFromAmpPlayground) {
            $allowedSenders = array_merge($allowedSenders, $this->testAmpSourceOrigins);
        }

        return in_array($email, $allowedSenders, true);
    }
}
