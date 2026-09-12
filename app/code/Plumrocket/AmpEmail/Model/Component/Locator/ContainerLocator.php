<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\Locator;

/**
 * Class RegistryLocator
 *
 * Used for access to base information during render components
 */
class ContainerLocator extends \Magento\Framework\DataObject implements
    \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * ContainerLocator constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param array                                             $data
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        parent::__construct($data);
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(int $customerId) : \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
    {
        if ($customerId) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $this->setCustomerGroupId((int) $customer->getGroupId());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->setCustomerGroupId(0);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->setCustomerGroupId(0);
            }
        }

        return $this->setData('customer_id', $customerId);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerGroupId(int $customerGroupId) : \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
    {
        return $this->setData('customer_group_id', $customerGroupId);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId(int $storeId) : \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * @inheritDoc
     */
    public function setRecipientEmail(string $email) : \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
    {
        return $this->setData('recipient_email', $email);
    }

    /**
     * @inheritDoc
     */
    public function setToken(string $tokenHash) : \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
    {
        return $this->setData('token', $tokenHash);
    }

    /**
     * @inheritDoc
     */
    public function setIsManualTestingMode(bool $flag) : \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
    {
        return $this->setData('manual_testing', $flag);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId() : int
    {
        return $this->_getData('customer_id') ?? 0;
    }

    /**
     * @inheritDoc
     */
    public function getCustomerGroupId() : int
    {
        return $this->_getData('customer_group_id') ?? 0;
    }

    /**
     * @inheritDoc
     */
    public function getStoreId() : int
    {
        return $this->_getData('store_id') ?? 0;
    }

    /**
     * @inheritDoc
     */
    public function getRecipientEmail() : string
    {
        return $this->_getData('recipient_email') ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getToken() : string
    {
        return $this->_getData('token') ?? '';
    }

    /**
     * @inheritDoc
     */
    public function isManualTestingMode() : bool
    {
        return $this->_getData('manual_testing') ?? false;
    }

    /**
     * @inheritDoc
     */
    public function resetData() : \Plumrocket\AmpEmailApi\Model\LocatorInterface
    {
        return $this->unsetData();
    }
}
