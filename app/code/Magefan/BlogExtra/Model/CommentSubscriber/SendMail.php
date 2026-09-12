<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Model\CommentSubscriber;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Magento\Store\Model\App\Emulation;

/**
 * Blog Extra SendMail model
 */
class SendMail extends AbstractModel
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Emulation
     */
    protected $emulation;

    /**
     * SendMail constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param ScopeConfigInterface $scopeConfig
     * @param Emulation $emulation
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LoggerInterface $logger,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        Emulation $emulation,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->emulation = $emulation;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @param $subscriber
     * @param $lastComment
     */
    public function sendEmail($subscriber, $lastComment)
    {
        try {
            // starting the store emulation with area defined for frontend
            $this->emulation->startEnvironmentEmulation($lastComment->getStoreId(), 'frontend');

            $this->inlineTranslation->suspend();

            $senderIdentity = $this->getConfigValue('mfblog/post_view/comments/sender_form');
            $sender = [
                 'name' => $this->getConfigValue('trans_email/ident_' . $senderIdentity . '/name'),
                 'email' => $this->getConfigValue('trans_email/ident_' . $senderIdentity . '/email')
            ];
            $transport = $this->transportBuilder
                ->setTemplateIdentifier(
                    // this code must get email template from configs
                    $this->getConfigValue('mfblog/post_view/comments/email_template')
                )
                ->setTemplateOptions([
                    // this is using frontend area to get the template file
                    'area' => Area::AREA_FRONTEND,
                    'store' => Store::DEFAULT_STORE_ID
                ])
                ->setTemplateVars([
                    'unsubscribe_link' => $subscriber->getUnsubscribeLink(),
                    'unsubscribe_all_link' => $subscriber->getUnsubscribeAllLink(),
                    'post' => $lastComment->getPost(),
                    'url' => $lastComment->getPost()->getPostUrl()
                ])
                ->setFrom($sender)
                ->addTo($subscriber->getData('email'))
                ->getTransport();

            $transport->sendMessage();

            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }

        // discard the emulated environment
        $this->emulation->stopEnvironmentEmulation();
    }

    /**
     * @param string $path
     * @return mixed
     */
    protected function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}
