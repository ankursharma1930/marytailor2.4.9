<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @method bool|null getPrampEmailEnable()
 */
class AmpTemplate extends \Magento\Email\Model\Template implements AmpTemplateInterface
{
    /**
     * @var AmpTemplateFactory
     */
    private $ampTemplateFactory;

    /**
     * @var \Plumrocket\AmpEmail\Model\Template\Config
     */
    private $ampEmailConfig;

    /**
     * @var mixed
     */
    private $isLoaded;

    /**
     * @var \Plumrocket\AmpEmail\Model\Template\Divider
     */
    private $emailFileDivider;

    /**
     * @var \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface
     */
    private $emailAddressParser;

    /**
     * @var \Plumrocket\AmpEmail\Model\Template\FilterFactory
     */
    private $ampFilterFactory;

    /**
     * @var \Plumrocket\AmpEmail\Model\Template\AddSignature
     */
    private $addSignature;

    /**
     * AmpTemplate constructor.
     *
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\View\DesignInterface                      $design
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Store\Model\App\Emulation                           $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Magento\Framework\View\Asset\Repository                     $assetRepo
     * @param \Magento\Framework\Filesystem                                $filesystem
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Email\Model\Template\Config                         $emailConfig
     * @param \Magento\Email\Model\TemplateFactory                         $templateFactory
     * @param \Magento\Framework\Filter\FilterManager                      $filterManager
     * @param \Magento\Framework\UrlInterface                              $urlModel
     * @param \Magento\Email\Model\Template\FilterFactory                  $filterFactory
     * @param \Plumrocket\AmpEmail\Model\Email\AmpTemplateFactory          $ampTemplateFactory
     * @param \Plumrocket\AmpEmail\Model\Template\Config                   $ampEmailConfig
     * @param \Plumrocket\AmpEmail\Model\Template\Divider                  $emailFileDivider
     * @param \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface $emailAddressParser
     * @param \Plumrocket\AmpEmail\Model\Template\FilterFactory            $ampFilterFactory
     * @param \Plumrocket\AmpEmail\Model\Template\AddSignature             $addSignature
     * @param array                                                        $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null            $serializer
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Email\Model\Template\Config $emailConfig,
        \Magento\Email\Model\TemplateFactory $templateFactory,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\UrlInterface $urlModel,
        \Magento\Email\Model\Template\FilterFactory $filterFactory,
        \Plumrocket\AmpEmail\Model\Email\AmpTemplateFactory $ampTemplateFactory,
        \Plumrocket\AmpEmail\Model\Template\Config $ampEmailConfig,
        \Plumrocket\AmpEmail\Model\Template\Divider $emailFileDivider,
        \Plumrocket\AmpEmail\Model\Email\EmailAddressParserInterface $emailAddressParser,
        \Plumrocket\AmpEmail\Model\Template\FilterFactory $ampFilterFactory,
        \Plumrocket\AmpEmail\Model\Template\AddSignature $addSignature,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct(
            $context,
            $design,
            $registry,
            $appEmulation,
            $storeManager,
            $assetRepo,
            $filesystem,
            $scopeConfig,
            $emailConfig,
            $templateFactory,
            $filterManager,
            $urlModel,
            $filterFactory,
            $data,
            $serializer
        );
        $this->ampTemplateFactory = $ampTemplateFactory;
        $this->ampEmailConfig = $ampEmailConfig;
        $this->emailFileDivider = $emailFileDivider;
        $this->emailAddressParser = $emailAddressParser;
        $this->ampFilterFactory = $ampFilterFactory;
        $this->addSignature = $addSignature;
    }

    /**
     * Rewrite default content on AMP
     *
     * @return string|null
     */
    public function getTemplateText()
    {
        return $this->getPrampEmailContent();
    }

    /**
     * Change Email filter to AMP Email filter
     *
     * @return \Plumrocket\AmpEmail\Model\Template\FilterFactory
     */
    protected function getFilterFactory() //@codingStandardsIgnoreLine
    {
        return $this->ampFilterFactory;
    }

    /**
     * @inheritDoc
     */
    public function loadTemplate() : AmpTemplateInterface
    {
        $templateId = $this->getId();
        if (is_numeric($templateId)) {
            $this->load($templateId);
        } else {
            $this->loadDefault($templateId);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function canRenderAmpForEmail(\Plumrocket\AmpEmail\Model\Email\AmpMessageInterface $message = null) : bool
    {
        $canRender = $this->isEnabledAmpForEmail() && $this->isExistAmpForEmail();

        if ($canRender
            && $message
            && $this->isAmpForEmailInSandbox()
            && $this->getPrampEmailTestingMethod() === self::TESTING_METHOD_AUTO
        ) {
            $canRender = (bool) array_intersect($message->getRecipientList(), $this->getPrampEmailAutomaticEmails());
        }

        return $canRender;
    }

    /**
     * @inheritDoc
     */
    public function isExistAmpForEmail() : bool
    {
        return (bool) $this->getPrampEmailContent();
    }

    /**
     * @inheritDoc
     */
    public function isEnabledAmpForEmail() : bool
    {
        return (bool) $this->getPrampEmailEnable();
    }

    /**
     * @inheritDoc
     */
    public function isAmpForEmailInSandbox() : bool
    {
        return self::AMP_EMAIL_STATUS_SANDBOX === $this->getPrampEmailMode();
    }

    /**
     * @inheritDoc
     */
    public function isAmpForEmailInLive() : bool
    {
        return self::AMP_EMAIL_STATUS_LIVE === $this->getPrampEmailMode();
    }

    /**
     * @inheritDoc
     */
    public function getPrampEmailContent() : string
    {
        return (string) str_replace(
            [
                '{{depend order.getIsNotVirtual()}}',
                '{{var order.getShippingDescription()}}',
                '{{depend order.getEmailCustomerNote()}}',
                '{{var order.getEmailCustomerNote()|escape|nl2br}}',
            ],
            [
                '{{depend order_data.is_not_virtual}}',
                '{{var order.shipping_description}}',
                '{{depend order_data.email_customer_note}}',
                '{{var order_data.email_customer_note|escape|nl2br}}',
            ],
            (string) $this->_getData('pramp_email_content')
        );
    }

    /**
     * @inheritDoc
     */
    public function setPrampEmailContent(string $content) : AmpTemplateInterface
    {
        $this->setData('pramp_email_content', $content);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPrampEmailStyles() : string
    {
        return (string) $this->_getData('pramp_email_styles');
    }

    /**
     * @inheritDoc
     */
    public function setPrampEmailStyles(string $styles) : AmpTemplateInterface
    {
        $this->setData('pramp_email_styles', $styles);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPrampEmailMode() : string
    {
        return (string) $this->_getData('pramp_email_mode');
    }

    /**
     * @inheritDoc
     */
    public function setPrampEmailMode($emailMode) : AmpTemplateInterface
    {
        $this->setData('pramp_email_mode', $emailMode);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPrampEmailTestingMethod() : string
    {
        return (string) $this->_getData('pramp_email_testing_method');
    }

    /**
     * @inheritDoc
     */
    public function setPrampEmailTestingMethod($emailTestingMethod) : AmpTemplateInterface
    {
        $this->setData('pramp_email_testing_method', $emailTestingMethod);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPrampEmailAutomaticEmails() : array
    {
        return $this->emailAddressParser->getValidEmails((string) $this->_getData('pramp_email_automatic_emails'));
    }

    /**
     * @inheritDoc
     */
    public function setPrampEmailAutomaticEmails($emails) : AmpTemplateInterface
    {
        $this->setData('pramp_email_automatic_emails', $emails);
        return $this;
    }

    /**
     * Simplify render template because, amp template cannot be plain
     *
     * @param string $configPath
     * @param array  $variables
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function getTemplateContent($configPath, array $variables)
    {
        $template = $this->getTemplateInstance();

        // Ensure child templates have the same area/store context as parent
        $template->setDesignConfig($this->getDesignConfig()->toArray())
                 ->setIsChildTemplate(true)
                 ->loadByConfigPath($configPath)
                 ->setTemplateType(self::TYPE_HTML)
                 ->setIsChildTemplate(true);

        return $template->getProcessedTemplate($variables);
    }

    /**
     * Added custom parser
     *
     * @param string $templateId
     * @return $this|\Magento\Email\Model\Template
     */
    public function loadDefault($templateId)
    {
        $designParams = $this->getDesignParams();
        $templateFile = $this->ampEmailConfig->getTemplateFilename($templateId, $designParams);
        $this->setTemplateType(self::TYPE_HTML);

        $rootDirectory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);

        try {
            $templateText = $rootDirectory->readFile($rootDirectory->getRelativePath($templateFile));
        } catch (\Magento\Framework\Exception\FileSystemException $fileSystemException) {
            $this->_logger->critical($fileSystemException);
            $templateText = '';
        }

        if ($templateText) {
            /**
             * trim copyright message
             */
            if (preg_match('/^<!--[\w\W]+?-->/m', $templateText, $matches)
                && strpos($matches[0], 'Copyright') !== false
            ) {
                $templateText = str_replace($matches[0], '', $templateText);
            }

            $templateText = preg_replace('/<!--@subject\s*(.*?)\s*@-->/u', '', $templateText);

            if (preg_match('/<!--@vars\s*((?:.)*?)\s*@-->/us', $templateText, $matches)) {
                $this->setData('orig_template_variables', str_replace("\n", '', $matches[1]));
                $templateText = str_replace($matches[0], '', $templateText);
            }

            // Remove comment lines and extra spaces
            $templateText = trim(preg_replace('#\{\*.*\*\}#suU', '', $templateText));

            $templateParts = $this->emailFileDivider->divideIntoParts($templateText);

            $this->setPrampEmailContent($templateParts['content']);
            $this->setPrampEmailStyles($templateParts['styles']);
            $this->setTemplateText($templateParts['content']);
        }

        $this->setId($templateId);
        $this->isLoaded = $templateId;

        return $this;
    }

    /**
     * Amp content cannot be plain
     *
     * @return boolean
     */
    public function isPlain()
    {
        return false;
    }

    /**
     * Amp content cannot be plain
     *
     * @return int
     */
    public function getType()
    {
        return self::TYPE_HTML;
    }

    /**
     * Prevent double load
     *
     * @return \Magento\Email\Model\Template
     */
    protected function _afterLoad()
    {
        $this->isLoaded = $this->getId();
        return parent::_afterLoad();
    }

    /**
     * Set default amp variables
     *
     * @inheritDoc
     */
    protected function addEmailVariables($variables, $storeId) //@codingStandardsIgnoreLine we need extend this method
    {
        $variables = parent::addEmailVariables($variables, $storeId);

        if (! isset($variables['pramp_email_styles'])) {
            $variables['pramp_email_styles'] = $this->addSignature->execute($this->getPrampEmailStyles(), 'view');
        }

        return $variables;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Email\AmpTemplate
     */
    protected function getTemplateInstance() //@codingStandardsIgnoreLine we need extend this method
    {
        return $this->ampTemplateFactory->create();
    }
}
