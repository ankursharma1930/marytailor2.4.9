<?php

namespace Dolphin\Productfaq\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;
use Dolphin\Productfaq\Model\ProductfaqFactory;
use Magento\Framework\Event\Observer;

class RegisterSuccess implements ObserverInterface
{
    /**
     * @var ProductfaqFactory
     */
    protected $productfaqFactory;

    /**
     * Constructor
     *
     * @param ProductfaqFactory $productfaqFactory
     */
    public function __construct(
        ProductfaqFactory $productfaqFactory
    ) {
        $this->productfaqFactory = $productfaqFactory;
    }

    /**
     * Execute method to update customer ID in product FAQ entries
     *
     * @param Observer $observer
     */
    public function execute(
        Observer $observer
    ) {
        $customer = $observer->getEvent()->getData('customer');

        $faqModel = $this->productfaqFactory->create();
        $ids = $faqModel->getCollection()
            ->addFieldToFilter("email_faq", $customer->getEmail())->getAllIds();

        foreach ($ids as $id) {
            $faqModel->setId($id)->setCustomerId($customer->getId())->save();
        }
    }
}
