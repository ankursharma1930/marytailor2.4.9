<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model;

/**
 * Class LoginCustomerById
 *
 * @since 1.0.1
 */
class LoginCustomerById implements \Plumrocket\AmpEmail\Model\LoginCustomerByIdInterface
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * LoginCustomerById constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session                   $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int $customerId
     * @param int $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(int $customerId, int $storeId) : bool
    {
        if (! $customerId) {
            return false;
        }

        $customer = $this->customerRepository->getById($customerId);

        if ($this->customerSession->isLoggedIn()
            && (int) $this->customerSession->getCustomer()->getId() !== $customerId
        ) {
            $this->customerSession->logout()->regenerateId();
        }

        $customer->setWebsiteId($this->storeManager->getStore($storeId)->getWebsiteId());

        $this->customerSession->setCustomerId($customer->getId());
        if ($this->customerSession->loginById($customer->getId())) {
            $this->customerSession->regenerateId();
        }

        return true;
    }
}
