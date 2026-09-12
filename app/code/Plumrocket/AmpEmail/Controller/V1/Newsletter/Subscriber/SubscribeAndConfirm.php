<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\AmpEmail\Controller\V1\Newsletter\Subscriber;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\ScopeInterface;

class SubscribeAndConfirm extends \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
{
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var \Magento\Framework\Validator\EmailAddress
     */
    private $emailValidator;

    /**
     * @var \Magento\Customer\Model\Url
     */
    private $customerUrl;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    private $customerAccountManagement;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * SubscribeAndConfirm constructor.
     *
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Magento\Store\Model\App\Emulation                   $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory     $ampJsonFactory
     * @param \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface      $corsValidator
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository
     * @param \Magento\Newsletter\Model\SubscriberFactory          $subscriberFactory
     * @param \Magento\Framework\Validator\EmailAddress            $emailValidator
     * @param \Magento\Customer\Model\Url                          $customerUrl
     * @param \Magento\Framework\App\Config\ScopeConfigInterface   $scopeConfig
     * @param \Magento\Customer\Api\AccountManagementInterface     $customerAccountManagement
     * @param \Magento\Customer\Api\CustomerRepositoryInterface    $customerRepository
     * @param \Psr\Log\LoggerInterface                             $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory $ampJsonFactory,
        \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface $corsValidator,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\Validator\EmailAddress $emailValidator,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context, $appEmulation, $storeManager, $ampJsonFactory, $corsValidator, $tokenRepository);
        $this->subscriberFactory = $subscriberFactory;
        $this->emailValidator = $emailValidator;
        $this->customerUrl = $customerUrl;
        $this->scopeConfig = $scopeConfig;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Subscription confirm action
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    public function execute()
    {
        $this->startEmulationForAmp();

        $currentAction = (string) $this->getRequest()->getParam('current_action');

        switch ($currentAction) {
            case 'subscribe':
                $ampJsonResult = $this->subscribe();
                break;
            case 'confirm':
                $ampJsonResult = $this->confirm();
                break;
            default:
                $ampJsonResult = $this->ampJsonFactory
                    ->create()
                    ->addErrorMessage(__('Invalid param "current_action".'));
        }

        $this->stopEmulation();

        return $ampJsonResult;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    private function subscribe() : \Plumrocket\AmpEmail\Model\Result\AmpJson
    {
        $ampJsonResult = $this->ampJsonFactory->create();

        $email = (string) $this->getRequest()->getParam('email');
        $storeId = (int) $this->getRequest()->getParam('store');
        $customerId = $this->getTokenModel()->getCustomerId();

        $sectionClasses = [
            'subscribe' => 'no-display',
            'confirm' => 'no-display',
            'thanks' => 'no-display',
            'already' => 'no-display',
            'request_sent' => 'no-display',
        ];

        if ($email) {
            try {
                $this->validateEmailFormat($email);
                $this->validateGuestSubscription($customerId);
                $this->validateEmailAvailable($email, $storeId, $customerId);

                /** @var Subscriber $subscriber */
                $subscriber = $this->subscriberFactory->create()->loadByEmail($email);
                if ($subscriber->getId()
                    && (int) $subscriber->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED
                ) {
                    throw new LocalizedException(
                        __('This email address is already subscribed.')
                    );
                }

                /** @var Subscriber $subscriber */
                $subscriber = $this->subscriberFactory->create(); // create subscriber same as in magento action
                $status = (int) $subscriber->subscribe($email);
                if ($status === Subscriber::STATUS_NOT_ACTIVE) {
                    $ampJsonResult->addSuccessMessage(__('The confirmation request has been sent.'));
                    if ($this->getTokenModel()->getRecipientEmail() === $email) {
                        $ampJsonResult
                            ->addData('subscriberId', $subscriber->getId())
                            ->addData('confirmCode', $subscriber->getCode())
                            ->addData('nextAction', 'confirm');

                        $sectionClasses['confirm'] = 'confirm-form';
                    } else {
                        $sectionClasses['request_sent'] = 'success-message';
                    }
                } else {
                    $ampJsonResult->addSuccessMessage(__('Thank you for your subscription.'));
                    $sectionClasses['thanks'] = 'success-message';
                }
            } catch (LocalizedException $e) { //@codingStandardsIgnoreLine
                $ampJsonResult->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $ampJsonResult->addExceptionMessage($e, __('Something went wrong with the subscription.'));
            }
        } else {
            $ampJsonResult->addErrorMessage(__('Required param "email" not passed.'));
        }

        foreach ($sectionClasses as $section => $cssClass) {
            $ampJsonResult->addData('class_' . $section, $cssClass);
        }

        return $ampJsonResult;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    private function confirm() : \Plumrocket\AmpEmail\Model\Result\AmpJson
    {
        $ampJsonResult = $this->ampJsonFactory->create();

        $subscriberId = (int) $this->getRequest()->getParam('subscriber_id');
        $subscriberCode = (string) $this->getRequest()->getParam('subscriber_code');

        $sectionClasses = [
            'subscribe' => 'no-display',
            'confirm' => 'no-display',
            'thanks' => 'no-display',
            'already' => 'no-display',
            'request_sent' => 'no-display',
        ];

        if ($subscriberId && $subscriberCode) {
            /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
            $subscriber = $this->subscriberFactory->create()->load($subscriberId);

            if ($subscriber->getId() && $subscriber->getCode()) {
                if ($subscriber->confirm($subscriberCode)) {
                    $ampJsonResult->addSuccessMessage(__('Your subscription has been confirmed.'));
                    $sectionClasses['thanks'] = 'success-message';
                } else {
                    $ampJsonResult->addErrorMessage(__('This is an invalid subscription confirmation code.'));
                }
            } else {
                $ampJsonResult->addErrorMessage(__('This is an invalid subscription ID.'));
            }
        } else {
            $ampJsonResult->addErrorMessage(__('Required param "id" or "code" not passed.'));
        }

        foreach ($sectionClasses as $section => $cssClass) {
            $ampJsonResult->addData('class_' . $section, $cssClass);
        }

        return $ampJsonResult;
    }

    /**
     * Validates that the email address isn't being used by a different account.
     *
     * @param string $email
     * @param int    $storeId
     * @param int    $customerId
     * @throws LocalizedException
     * @return void
     */
    private function validateEmailAvailable($email, $storeId, $customerId)
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        if ($customerId && $this->getCustomer($customerId)
            && ($this->getCustomer($customerId)->getEmail() !== $email
                && ! $this->customerAccountManagement->isEmailAvailable($email, $websiteId)
            )
        ) {
            throw new LocalizedException(
                __('This email address is already assigned to another user.')
            );
        }
    }

    /**
     * Validates that if the current user is a guest, that they can subscribe to a newsletter.
     *
     * @param int $customerId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function validateGuestSubscription(int $customerId)
    {
        if ($customerId) {
            return;
        }

        $allowedForGuest = (int) $this->scopeConfig->getValue(
            Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG,
            ScopeInterface::SCOPE_STORE
        ) !== 1;

        if ($allowedForGuest) {
            throw new LocalizedException(
                __(
                    'Sorry, but the administrator denied subscription for guests. Please <a href="%1">register</a>.',
                    $this->customerUrl->getRegisterUrl()
                )
            );
        }
    }

    /**
     * Validates the format of the email address
     *
     * @param string $email
     * @throws LocalizedException
     * @return void
     */
    private function validateEmailFormat(string $email)
    {
        if (!$this->emailValidator->isValid($email)) {
            throw new LocalizedException(__('Please enter a valid email address.'));
        }
    }

    /**
     * @param int $customerId
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    private function getCustomer(int $customerId)
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return false;
        }
    }
}
