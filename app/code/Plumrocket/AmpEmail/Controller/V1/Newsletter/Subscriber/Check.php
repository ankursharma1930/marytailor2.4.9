<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\AmpEmail\Controller\V1\Newsletter\Subscriber;

use Magento\Newsletter\Model\Subscriber;

class Check extends \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
{
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * Check constructor.
     *
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Magento\Store\Model\App\Emulation                   $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory     $ampJsonFactory
     * @param \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface      $corsValidator
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository
     * @param \Magento\Newsletter\Model\SubscriberFactory          $subscriberFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory $ampJsonFactory,
        \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface $corsValidator,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
        parent::__construct($context, $appEmulation, $storeManager, $ampJsonFactory, $corsValidator, $tokenRepository);
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * Subscription confirm action
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    public function execute()
    {
        $ampJsonResult = $this->ampJsonFactory->create();

        $this->startEmulationForAmp();

        $sectionClasses = [
            'subscribe' => 'no-display',
            'confirm' => 'no-display',
            'already' => 'no-display',
            'thanks' => 'no-display',
        ];

        $subscriberId = (int) $this->getRequest()->getParam('id');
        $email = $this->getTokenModel()->getRecipientEmail();

        if ($subscriberId || $email) {
            /** @var Subscriber $subscriber */
             $subscriber = $this->subscriberFactory->create();

            if ($subscriberId) {
                $subscriber->load($subscriberId);
            } else {
                $subscriber->loadByEmail($email);
            }

            $ampJsonResult->addData('email', $email);

            if ($subscriber->getId()) {
                if (Subscriber::STATUS_NOT_ACTIVE === (int) $subscriber->getStatus() && $subscriber->getCode()) {
                    $sectionClasses['confirm'] = 'confirm-form';
                    $ampJsonResult->addData('nextAction', 'confirm');
                    $ampJsonResult->addData('confirmCode', $subscriber->getCode());
                    $ampJsonResult->addData('subscriberId', $subscriber->getId());
                } elseif (Subscriber::STATUS_SUBSCRIBED === (int) $subscriber->getStatus()) {
                    $sectionClasses['already'] = 'already-done';
                } else {
                    $sectionClasses['subscribe'] = 'subscribe-form';
                    $ampJsonResult->addData('nextAction', 'subscribe');
                }
            } else {
                $sectionClasses['subscribe'] = 'subscribe-form';
                $ampJsonResult->addData('nextAction', 'subscribe');
            }
        } else {
            $ampJsonResult->addErrorMessage(__('Required param "email" not passed.'));
        }

        foreach ($sectionClasses as $section => $cssClass) {
            $ampJsonResult->addData('class_' . $section, $cssClass);
        }

        $this->stopEmulation();

        return $ampJsonResult->setIsSingleListItem(true);
    }
}
