<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\AmpEmail\Model\Email;

use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Exception\LocalizedException;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @var \Plumrocket\AmpEmail\Model\Email\MessageFactory
     */
    private $ampMessageFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Used for manual testing
     *
     * @var array
     */
    private $testTemplateData = [];

    /**
     * Used for manual testing
     *
     * @var array
     */
    private $testAmpTemplateData = [];

    /**
     * @var \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    private $componentDataLocator;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Plumrocket\AmpEmail\Model\Magento\VersionProvider
     */
    private $versionProvider;

    /**
     * @var \Plumrocket\AmpEmail\Model\Email\Old\TransportFactory
     */
    private $oldTransportFactory;

    /**
     * @var \Plumrocket\AmpEmail\Helper\Config
     */
    private $config;

    /**
     * @param \Magento\Framework\Mail\Template\FactoryInterface         $templateFactory
     * @param \Magento\Framework\Mail\MessageInterface                  $message
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface  $senderResolver
     * @param \Magento\Framework\ObjectManagerInterface                 $objectManager
     * @param \Magento\Framework\Mail\TransportInterfaceFactory         $mailTransportFactory
     * @param \Plumrocket\AmpEmail\Model\Email\AmpMessageFactory        $ampMessageFactory
     * @param \Psr\Log\LoggerInterface                                  $logger
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator
     * @param \Magento\Framework\Event\ManagerInterface                 $eventManager
     * @param \Plumrocket\AmpEmail\Model\Magento\VersionProvider        $versionProvider
     * @param \Plumrocket\AmpEmail\Model\Email\Old\TransportFactory     $oldTransportFactory
     * @param \Plumrocket\AmpEmail\Helper\Config                        $config
     */
    public function __construct(
        \Magento\Framework\Mail\Template\FactoryInterface $templateFactory,
        \Magento\Framework\Mail\MessageInterface $message,
        \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Mail\TransportInterfaceFactory $mailTransportFactory,
        \Plumrocket\AmpEmail\Model\Email\AmpMessageFactory $ampMessageFactory,
        \Psr\Log\LoggerInterface $logger,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Plumrocket\AmpEmail\Model\Magento\VersionProvider $versionProvider,
        \Plumrocket\AmpEmail\Model\Email\Old\TransportFactory $oldTransportFactory,
        \Plumrocket\AmpEmail\Helper\Config $config
    ) {
        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory
        );

        if ($config->isModuleEnabled()) {
            $this->message = $ampMessageFactory->create();
            $this->ampMessageFactory = $ampMessageFactory;
        }

        $this->logger = $logger;
        $this->componentDataLocator = $componentDataLocator;
        $this->eventManager = $eventManager;
        $this->versionProvider = $versionProvider;
        $this->oldTransportFactory = $oldTransportFactory;
        $this->config = $config;
    }

    /**
     * Used for manual testing
     *
     * @param array $data
     * @return $this
     */
    public function setTestTemplateData(array $data)
    {
        $this->testTemplateData = $data;

        return $this;
    }

    /**
     * Used for manual testing
     *
     * @return array
     */
    public function getTestTemplateData() : array
    {
        return $this->testTemplateData;
    }

    /**
     * Used for manual testing
     *
     * @param array $data
     * @return $this
     */
    public function setTestAmpTemplateData(array $data)
    {
        $this->testAmpTemplateData = $data;

        return $this;
    }

    /**
     * Used for manual testing
     *
     * @return array
     */
    public function getTestAmpTemplateData() : array
    {
        return $this->testAmpTemplateData;
    }

    /**
     * Rewrite message class
     *
     * @return $this|\Magento\Framework\Mail\Template\TransportBuilder
     */
    protected function reset()
    {
        parent::reset(); //@codingStandardsIgnoreLine it's not Data Access Method

        if ($this->config->isModuleEnabled()) {
            $this->message = $this->ampMessageFactory->create();
        }

        return $this;
    }

    /**
     * Used only for autocomplete
     *
     * @return \Plumrocket\AmpEmail\Model\Email\AmpMessageInterface
     */
    protected function getMessage()
    {
        return $this->message;
    }

    /**
     * After base rendering - render amp template if it exist
     *
     * @throws LocalizedException if template type is unknown
     * @return $this
     */
    protected function prepareMessage()
    {
        if (! $this->config->isModuleEnabled()) {
            parent::prepareMessage();
            return $this;
        }

        if ($testAmpData = $this->getTestAmpTemplateData()) {
            return $this->manualTestingApproach($testAmpData);
        }

        $template = $this->getTemplate();
        $body = $template->processTemplate();
        switch ($template->getType()) {
            case TemplateTypesInterface::TYPE_TEXT:
                $this->message->setBodyText($body);
                break;

            case TemplateTypesInterface::TYPE_HTML:
                $this->message->setBodyHtml($body);
                break;

            default:
                throw new LocalizedException(
                    new \Magento\Framework\Phrase('Unknown template type')
                );
        }

        $this->message->setSubject(html_entity_decode($template->getSubject(), ENT_QUOTES));

        $ampEmailTemplate = $this->createAmpTemplate();
        if ($ampEmailTemplate
            && $this->getMessage() instanceof \Plumrocket\AmpEmail\Model\Email\AmpMessageInterface
            && $ampEmailTemplate->canRenderAmpForEmail($this->getMessage())
        ) {
            $this->componentDataLocator->setRecipientEmail($this->getMessage()->getMainRecipient());
            $this->getMessage()->setBodyAmp($ampEmailTemplate->processTemplate());
            $this->eventManager->dispatch(
                'pramp_email_template_render_after',
                ['message' => $this->getMessage()]
            );
            $this->logger->debug('AMP Email: content for template ID ' . $ampEmailTemplate->getId() . ' created');
        }

        return $this;
    }

    /**
     * Use logic like in magento <= 2.3.2
     * @since 1.1.0
     *
     * @param string $address
     * @param string $name
     * @return \Magento\Framework\Mail\Template\TransportBuilder
     */
    public function addTo($address, $name = '')
    {
        if ($this->message instanceof AmpMessageInterface) {
            $this->message->addTo($address);
        }

        return parent::addTo($address, $name);
    }

    /**
     * Use logic like in magento <= 2.3.2
     * @since 1.1.0
     *
     * @param string $address
     * @param string $name
     * @return \Magento\Framework\Mail\Template\TransportBuilder
     */
    public function addCc($address, $name = '')
    {
        if ($this->message instanceof AmpMessageInterface) {
            $this->message->addCc($address);
        }

        return parent::addCc($address, $name);
    }

    /**
     * Use logic like in magento <= 2.3.2
     * @since 1.1.0
     *
     * @param string $address
     * @return \Magento\Framework\Mail\Template\TransportBuilder
     */
    public function addBcc($address)
    {
        if ($this->message instanceof AmpMessageInterface) {
            $this->message->addBcc($address);
        }

        return parent::addBcc($address);
    }

    /**
     * @inheritDoc
     */
    public function setFrom($from)
    {
        return parent::setFrom($from);
    }

    /**
     * Use logic like in magento <= 2.3.2
     * @since 1.1.0
     *
     * @param array|string $from
     * @param null         $scopeId
     * @return $this|\Magento\Framework\Mail\Template\TransportBuilder
     */
    public function setFromByScope($from, $scopeId = null)
    {
        if ($this->message instanceof AmpMessageInterface) {
            $result = $this->_senderResolver->resolve($from, $scopeId);
            $this->message->setFromAddress($result['email'], $result['name']);
        }

        return parent::setFromByScope($from, $scopeId);
    }

    /**
     * Get mail transport which support m2.2.x - m2.3.3
     * @since 1.1.0
     *
     * @return \Magento\Framework\Mail\TransportInterface
     */
    public function getTransport()
    {
        try {
            $this->prepareMessage();
            if ($this->message instanceof AmpMessageInterface
                && $this->versionProvider->isMagentoVersionBelow('2.3.3')
            ) {
                $mailTransport = $this->mailTransportFactory->create(['message' => clone $this->message]);
            } else {
                $mailTransport = $this->oldTransportFactory->create(['message' => clone $this->message]);
            }
        } finally {
            $this->reset();
        }

        return $mailTransport;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface|false
     */
    private function createAmpTemplate()
    {
        try {
            return $this->getNewTemplate(AmpTemplateInterface::class)->loadTemplate();
        } catch (\UnexpectedValueException $e) {
            // There isn't amp email for this template
            return false;
        }
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Testing\Template
     */
    private function createTestDefaultTemplate()
    {
        return $this->getNewTemplate(\Plumrocket\AmpEmail\Model\Testing\Template::class);
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Testing\AmpTemplate
     */
    private function createTestAmpTemplate()
    {
        return $this->getNewTemplate(\Plumrocket\AmpEmail\Model\Testing\AmpTemplate::class);
    }

    /**
     * @param string $type
     * @return \Plumrocket\AmpEmail\Model\Testing\AmpTemplate|\Plumrocket\AmpEmail\Model\Testing\Template|\Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface
     */
    private function getNewTemplate(string $type)
    {
        $defaultTemplateModel = $this->templateModel;
        $this->templateModel = $type;
        $template = $this->getTemplate();
        $this->templateModel = $defaultTemplateModel;
        return $template;
    }

    /**
     * @param array $testAmpData
     * @return $this
     * @throws \Magento\Framework\Exception\MailException
     */
    private function manualTestingApproach(array $testAmpData)
    {
        $this->componentDataLocator->setIsManualTestingMode(true);
        $this->setTestAmpTemplateData([]);

        $ampEmailTemplate = $this->createTestAmpTemplate();
        $ampEmailTemplate->setPrampEmailContent($testAmpData['content'] ?? '');
        $ampEmailTemplate->setPrampEmailStyles($testAmpData['styles'] ?? '');

        if (isset($this->templateVars['customer'])) {
            $this->componentDataLocator->setRecipientEmail($this->templateVars['customer']->getEmail());
        } else {
            $this->componentDataLocator->setRecipientEmail($this->getMessage()->getMainRecipient());
        }

        $this->getMessage()->setBodyAmp($ampEmailTemplate->processTemplate());
        $this->eventManager->dispatch(
            'pramp_email_template_render_after',
            ['message' => $this->getMessage()]
        );

        $template = $this->createTestDefaultTemplate();

        if ($testData = $this->getTestTemplateData()) {
            $type = $testData['is_html'] ? TemplateTypesInterface::TYPE_HTML : TemplateTypesInterface::TYPE_TEXT;
            $template->setTemplateSubject($testData['template_subject']);
            $template->setTemplateType($type);
            $template->setTemplateText($testData['template_text']);
            if (TemplateTypesInterface::TYPE_HTML === $type) {
                $template->setTemplateStyles($testData['template_styles']);
            }

            $body = $template->processTemplate();
        } else {
            $body = $template->processTemplate();
            $type = $template->getType();
        }

        switch ($type) {
            case TemplateTypesInterface::TYPE_TEXT:
                $this->message->setBodyText($body);
                break;

            case TemplateTypesInterface::TYPE_HTML:
                $this->message->setBodyHtml($body);
                break;

            default:
                $this->getMessage()->setBodyHtml('Test html content generated for testing amp email in manual mode.');
        }

        $this->message->setSubject(html_entity_decode($template->getSubject(), ENT_QUOTES)); //@codingStandardsIgnoreLine magento code
        return $this;
    }
}
