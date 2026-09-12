<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\AmpEmail\Controller\Adminhtml\Customer\Order;

class ListAction extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @var \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface
     */
    private $emailAddressParser;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * ListAction constructor.
     *
     * @param \Magento\Backend\App\Action\Context                                 $context
     * @param \Magento\Customer\Api\CustomerRepositoryInterface                   $customerRepository
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface $collectionFactory
     * @param \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface        $emailAddressParser
     * @param \Psr\Log\LoggerInterface                                            $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface $collectionFactory,
        \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface $emailAddressParser,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->customerRepository = $customerRepository;
        $this->collectionFactory = $collectionFactory;
        $this->emailAddressParser = $emailAddressParser;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);

        $customerEmails = $this->emailAddressParser->getValidEmails(
            $this->getRequest()->getParam('email', '')
        );
        $customerEmail = $customerEmails[0] ?? '';

        if (! $customerEmail) {
            $result->setHttpResponseCode(400);
            return $result->setData(['message' => __('Invalid email format')]);
        }

        try {
            $orders = $this->getOrdersForCustomer($customerEmail);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $noSuchEntityException) {
            $orders = $this->getOrdersForGuest($customerEmail);
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            $this->logger->critical($localizedException);
            return $result->setHttpResponseCode(500)->setData(['message' => 'Something went wrong']);
        }

        $orderOptionArray = [];
        foreach ($orders as $order) {
            $orderOptionArray[] = [
                'value' => $order->getEntityId(),
                'label' => $order->getIncrementId()
            ];
        }

        if (empty($orderOptionArray)) {
            $result->setHttpResponseCode(400);
            return $result->setData(['message' => __("We cannot find orders by email \"$customerEmail\"")]);
        }

        return $result->setData($orderOptionArray);
    }

    /**
     * @param string $customerEmail
     * @return \Magento\Sales\Api\Data\OrderInterface[] Array of collection items.
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getOrdersForCustomer(string $customerEmail) : array
    {
        $customer = $this->customerRepository->get($customerEmail);
        $collection = $this->collectionFactory->create($customer->getId());
        $collection->addFieldToSelect(
            'entity_id'
        )->addFieldToSelect(
            'increment_id'
        );

        return $collection->getItems();
    }

    /**
     * @param string $customerEmail
     * @return \Magento\Sales\Api\Data\OrderInterface[] Array of collection items.
     */
    private function getOrdersForGuest(string $customerEmail) : array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(
            'entity_id'
        )->addFieldToSelect(
            'increment_id'
        )->addFieldToFilter(
            'customer_email',
            $customerEmail
        );

        return $collection->getItems();
    }
}
