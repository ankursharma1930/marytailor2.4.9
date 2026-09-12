<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Controller\Adminhtml\Test\Email;

use Magento\Framework\Exception\ValidatorException;

/**
 * Class Send
 * @since 1.0.1
 */
class Send extends \Magento\Backend\App\Action
{
    /**
     * @var \Plumrocket\AmpEmail\Model\Email\TransportBuilder
     */
    private $transportBuilder;

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
     * @var \Plumrocket\AmpEmail\Model\Testing\TestDataGenerator
     */
    private $testDataGenerator;

    /**
     * Send constructor.
     *
     * @param \Magento\Backend\App\Action\Context                          $context
     * @param \Plumrocket\AmpEmail\Model\Email\TransportBuilder            $transportBuilder
     * @param \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface $emailAddressParser
     * @param \Magento\Framework\Translate\Inline\StateInterface           $inlineTranslation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Psr\Log\LoggerInterface                                     $logger
     * @param \Plumrocket\AmpEmail\Model\Testing\TestDataGenerator         $testDataGenerator
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Plumrocket\AmpEmail\Model\Email\TransportBuilder $transportBuilder,
        \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface $emailAddressParser,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Plumrocket\AmpEmail\Model\Testing\TestDataGenerator $testDataGenerator
    ) {
        parent::__construct($context);

        $this->transportBuilder = $transportBuilder;
        $this->emailAddressParser = $emailAddressParser;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->testDataGenerator = $testDataGenerator;
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
            $postData = $this->validateAndPreparePostData($postData);
        } catch (\Magento\Framework\Exception\ValidatorException $localizedException) {
            return $result->setHttpResponseCode(400)->setData(['message' => $localizedException->getMessage()]);
        }

        try {
            $templateVars = $this->testDataGenerator->generate();
        } catch (\Magento\Framework\Exception\LocalizedException $localizedException) {
            return $result->setHttpResponseCode(400)->setData(['message' => $localizedException->getMessage()]);
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

            $this->transportBuilder->setTestAmpTemplateData(
                [
                    'content' => '<!doctype html>
<html amp4email>
<head>
  <meta charset="utf-8">
  <script async src="https://cdn.ampproject.org/v0.js"></script>
  <style amp4email-boilerplate>body{visibility:hidden}</style>
</head>
<body>
  Hello, AMP4EMAIL world.
</body>
</html>'
                ]
            );

            $this->transportBuilder->setTestTemplateData(
                [
                    'template_text' => 'This is the HTML content. To see dynamic emails sent from ' .
                        $senderInfo['email'] . ' in Gmail, whitelist ' . $senderInfo['email'] . ' in Gmail Settings' .
                        ' > General > Dynamic email > Developer settings.',
                    'template_styles' => '',
                    'template_subject' => 'Default Amp Email Test Template',
                    'is_html' => true,
                ]
            );

            $this->inlineTranslation->suspend();
            $this->transportBuilder->setTemplateIdentifier('pramp_send_test_email')->setTemplateOptions(
                [
                    'area' => $area,
                    'store' => $templateVars['store_id'],
                ]
            )->setTemplateVars(
                $templateVars
            )->setFrom(
                $senderInfo
            )->addTo(
                $postData['to'],
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
                    $postData['to'],
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
        $receivedCustomerEmail = $postData['to'] ?? '';
        $customerEmails = $this->emailAddressParser->getValidEmails($receivedCustomerEmail);
        if (isset($customerEmails[0])) {
            $postData['to'] = $customerEmails[0];
        } else {
            throw new ValidatorException(__('Customer email "%1" is invalid', $receivedCustomerEmail));
        }

        return $postData;
    }
}
