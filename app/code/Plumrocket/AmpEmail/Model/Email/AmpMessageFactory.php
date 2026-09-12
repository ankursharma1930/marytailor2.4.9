<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email;

class AmpMessageFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * AmpMessageFactory constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface          $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create instance depend on magento version
     *
     * @param array $data
     * @return AmpMessageInterface
     */
    public function create(array $data = []) : AmpMessageInterface
    {
        return $this->objectManager->create(AmpMessage::class, $data); //@codingStandardsIgnoreLine
    }
}
