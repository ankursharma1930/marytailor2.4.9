<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\AmpEmail\Controller\Adminhtml\Template;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\ValidatorException;

class Send extends \Magento\Backend\App\Action
{
    /**
     * @var \Plumrocket\AmpEmail\Model\Email\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface
     */
    private $emailAddressParser;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Plumrocket\AmpEmail\Model\Testing\CustomerDataGenerator
     */
    private $customerDataGenerator;

    /**
     * @var \Plumrocket\AmpEmail\Model\Testing\GuestDataGenerator
     */
    private $guestDataGenerator;

    /**
     * @var \Magento\Framework\Mail\Template\ConfigInterface
     */
    private $emailConfig;

    /**
     * @var \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface
     */
    private $componentProductLocator;

    /**
     * @var \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface
     */
    private $componentPriceAlertLocator;

    /**
     * Send constructor.
     *
     * @param \Magento\Backend\App\Action\Context                          $context
     * @param \Plumrocket\AmpEmail\Model\Email\TransportBuilder            $transportBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface            $customerRepository
     * @param \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface $emailAddressParser
     * @param \Magento\Framework\Translate\Inline\StateInterface           $inlineTranslation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Psr\Log\LoggerInterface                                     $logger
     * @param \Plumrocket\AmpEmail\Model\Testing\CustomerDataGenerator     $customerDataGenerator
     * @param \Plumrocket\AmpEmail\Model\Testing\GuestDataGenerator        $guestDataGenerator
     * @param \Magento\Email\Model\Template\Config                         $emailConfig
     * @param \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface    $componentProductLocator
     * @param \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface $componentPriceAlertLocator
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Plumrocket\AmpEmail\Model\Email\TransportBuilder $transportBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface $emailAddressParser,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Plumrocket\AmpEmail\Model\Testing\CustomerDataGenerator $customerDataGenerator,
        \Plumrocket\AmpEmail\Model\Testing\GuestDataGenerator $guestDataGenerator,
        \Magento\Email\Model\Template\Config $emailConfig,
        \Plumrocket\AmpEmail\Api\ComponentProductLocatorInterface $componentProductLocator,
        \Plumrocket\AmpEmail\Api\ComponentPriceAlertLocatorInterface $componentPriceAlertLocator
    ) {
        parent::__construct($context);

        $this->transportBuilder = $transportBuilder;
        $this->customerRepository = $customerRepository;
        $this->emailAddressParser = $emailAddressParser;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->customerDataGenerator = $customerDataGenerator;
        $this->guestDataGenerator = $guestDataGenerator;
        $this->emailConfig = $emailConfig;
        $this->componentProductLocator = $componentProductLocator;
        $this->componentPriceAlertLocator = $componentPriceAlertLocator;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);

        $postData = $this->getRequest()->getPostValue();
        $area = \Magento\Framework\App\Area::AREA_FRONTEND;

        try {
            $area = $this->emailConfig->getTemplateArea($postData['orig_template_code']);
            $templateCode = $postData['orig_template_code'];
        } catch (\UnexpectedValueException $unexpectedValueException) {
            $templateCode = 'pramp_test_frontend_template';
        }

        try {
            $postData = $this->validateAndPreparePostData($postData);
        } catch (\Magento\Framework\Exception\ValidatorException $localizedException) {
            return $result->setHttpResponseCode(400)->setData(['message' => $localizedException->getMessage()]);
        }

        try {
            $customer = $this->customerRepository->get($postData['customer_email']);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $noSuchEntityException) {
            $customer = false;
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            return $result->setHttpResponseCode(500)
                          ->setData(['message' => 'Something went wrong while get customer']);
        }

        try {
            if ($customer) {
                $templateVars = $this->getCustomerTemplateVars($customer, $postData, $area);
            } else {
                $templateVars = $this->getGuestTemplateVars($postData['customer_email'], $postData, $area);
            }
            $this->componentProductLocator->setProductIds($templateVars['orderProductIds'] ?? []);
            $this->initPriceAlertData($templateVars);
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            return $result->setHttpResponseCode(400)->setData(['message' => $localizedException->getMessage()]);
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            return $result->setHttpResponseCode(500)->setData(['message' => 'Something went wrong']);
        }

        try {
            $sender = 'general';

            $senderInfo['name'] = $this->scopeConfig->getValue(
                'trans_email/ident_' . $sender . '/name',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $templateVars['store_id']
            );
            $senderInfo['email'] = $this->scopeConfig->getValue(
                'trans_email/ident_' . $sender . '/email',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $templateVars['store_id']
            );

            $this->transportBuilder->setTestAmpTemplateData($postData);
            if (! empty($postData['template_text'])) {
                $this->transportBuilder->setTestTemplateData($postData);
            }

            $this->inlineTranslation->suspend();
            $this->transportBuilder->setTemplateIdentifier($templateCode)->setTemplateOptions(
                [
                    'area' => $area,
                    'store' => $templateVars['store_id'],
                ]
            )->setTemplateVars(
                $templateVars
            )->setFrom(
                $senderInfo
            )->addTo(
                $postData['test_email'],
                'AMP Email Tester'
            );

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            return $result->setHttpResponseCode(500)
                          ->setData(['message' => 'Something went wrong while sending email']);
        }

        return $result->setData(
            [
                'message' => __(
                    'AMP Email sent to %1.<br/> Sender: %2 &lt;%3&gt;.',
                    $postData['test_email'],
                    $senderInfo['name'],
                    $senderInfo['email']
                )
            ]
        );
    }

    /**
     * @param array $postData
     * @return array
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    private function validateAndPreparePostData(array $postData) : array
    {
        $receivedCustomerEmail = $postData['customer_email'] ?? '';
        $customerEmails = $this->emailAddressParser->getValidEmails($postData['customer_email'] ?? '');
        if (isset($customerEmails[0])) {
            $postData['customer_email'] = $customerEmails[0];
        } else {
            throw new ValidatorException(__('Customer email "%1" is invalid', $receivedCustomerEmail));
        }

        $receivedTestEmail = $postData['test_email'] ?? '';
        $testEmails = $this->emailAddressParser->getValidEmails($postData['test_email'] ?? '');
        if (isset($testEmails[0])) {
            $postData['test_email'] = $testEmails[0];
        } else {
            throw new ValidatorException(
                __('Test email "%1" is invalid. Please, provide valid email.', $receivedTestEmail)
            );
        }

        if (empty($postData['content'])) {
            throw new ValidatorException(__('Email content cannot be empty. Please, fill amp content for testing'));
        }

        if (empty($postData['customer_order'])) {
            throw new ValidatorException(__('Order is\'nt selected. Please, choose order for send test mail.'));
        }

        return $postData;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param array                                        $postData
     * @param string                                       $area
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCustomerTemplateVars(CustomerInterface $customer, array $postData, string $area) : array
    {
        return $this->customerDataGenerator->generate($customer, $postData, $area);
    }

    /**
     * @param string $email
     * @param array  $postData
     * @param string $area
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getGuestTemplateVars(string $email, array $postData, string $area) : array
    {
        return $this->guestDataGenerator->generate($email, $postData, $area);
    }

    /**
     * @param array $templateVars
     * @return $this
     */
    private function initPriceAlertData(array $templateVars)
    {
        if ($customerId = $templateVars['customer_id'] ?? 0) {
            foreach ($templateVars['initialAlertPrices'] as $productData) {
                $this->componentPriceAlertLocator->setInitialPrice($productData['id'], $productData['price']);
            }
        }

        return $this;
    }
}
