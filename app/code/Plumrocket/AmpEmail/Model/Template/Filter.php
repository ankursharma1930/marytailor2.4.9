<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Template;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\SecurityViolationException;
use Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface;

class Filter extends \Magento\Email\Model\Template\Filter
{
    /**
     * @var \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterfaceFactory
     */
    private $componentPartsCollectorFactory;

    /**
     * @var \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface|null
     */
    private $componentPartsCollector;

    /**
     * @var \Plumrocket\AmpEmail\Api\ComponentConfigResolverInterface
     */
    private $componentConfigResolver;

    /**
     * @var \Plumrocket\AmpEmail\Model\Component\DefaultValuesParser
     */
    private $defaultValuesParser;

    /**
     * @var \Plumrocket\AmpEmail\Api\AmpComponentLibraryJsInterface
     */
    private $ampComponentLibraryJs;

    /**
     * @var \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface
     */
    private $componentDataLocator;

    /**
     * @var \Plumrocket\Token\Api\GenerateForCustomerInterface
     */
    private $tokenGenerator;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Plumrocket\AmpEmail\Api\ClearAmpForEmailHtmlInterface
     */
    private $clearAmpForEmailHtml;

    /**
     * Filter constructor.
     *
     * @param \Magento\Framework\Stdlib\StringUtils                                 $string
     * @param \Psr\Log\LoggerInterface                                              $logger
     * @param \Magento\Framework\Escaper                                            $escaper
     * @param \Magento\Framework\View\Asset\Repository                              $assetRepo
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                    $scopeConfig
     * @param \Magento\Variable\Model\VariableFactory                               $coreVariableFactory
     * @param \Magento\Store\Model\StoreManagerInterface                            $storeManager
     * @param \Magento\Framework\View\LayoutInterface                               $layout
     * @param \Magento\Framework\View\LayoutFactory                                 $layoutFactory
     * @param \Magento\Framework\App\State                                          $appState
     * @param \Magento\Framework\UrlInterface                                       $urlModel
     * @param \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterfaceFactory   $componentPartsCollectorFactory
     * @param \Plumrocket\AmpEmail\Api\ComponentConfigResolverInterface             $componentConfigResolver
     * @param \Plumrocket\AmpEmail\Model\Component\DefaultValuesParser              $defaultValuesParser
     * @param \Plumrocket\AmpEmail\Api\AmpComponentLibraryJsInterface               $ampComponentLibraryJs
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface             $componentDataLocator
     * @param \Plumrocket\AmpEmail\Model\Magento\VersionProvider                    $versionProvider
     * @param \Plumrocket\Token\Api\GenerateForCustomerInterface                    $tokenGenerator
     * @param \Magento\Framework\ObjectManagerInterface                             $objectManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface                     $priceCurrency
     * @param \Plumrocket\AmpEmail\Api\ClearAmpForEmailHtmlInterface                $clearAmpForEmailHtml
     * @param \Magento\Variable\Model\Source\Variables                              $configVariables
     * @param array                                                                 $variables
     * @param \Magento\Framework\Css\PreProcessor\Adapter\CssInliner|null           $cssInliner
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Variable\Model\VariableFactory $coreVariableFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\UrlInterface $urlModel,
        \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterfaceFactory $componentPartsCollectorFactory,
        \Plumrocket\AmpEmail\Api\ComponentConfigResolverInterface $componentConfigResolver,
        \Plumrocket\AmpEmail\Model\Component\DefaultValuesParser $defaultValuesParser,
        \Plumrocket\AmpEmail\Api\AmpComponentLibraryJsInterface $ampComponentLibraryJs,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Plumrocket\AmpEmail\Model\Magento\VersionProvider $versionProvider,
        \Plumrocket\Token\Api\GenerateForCustomerInterface $tokenGenerator,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Plumrocket\AmpEmail\Api\ClearAmpForEmailHtmlInterface $clearAmpForEmailHtml,
        \Magento\Variable\Model\Source\Variables $configVariables,
        $variables = [],
        \Magento\Framework\Css\PreProcessor\Adapter\CssInliner $cssInliner = null
    ) {
        if ($versionProvider->isMagentoVersionBelow('2.4.3')) {
            $emogrifier = $objectManager->get('\Pelago\Emogrifier');

            $args = [
                $string,
                $logger,
                $escaper,
                $assetRepo,
                $scopeConfig,
                $coreVariableFactory,
                $storeManager,
                $layout,
                $layoutFactory,
                $appState,
                $urlModel,
                $emogrifier,
                $configVariables,
                $variables,
                $cssInliner
            ];
        } else {
            if (null === $cssInliner) {
                $cssInliner = $objectManager->get('\Magento\Framework\Css\PreProcessor\Adapter\CssInliner');
            }

            $variableResolver = $objectManager->get('\Magento\Framework\Filter\VariableResolverInterface');
            $cssProcessor = $objectManager->create('\Magento\Email\Model\Template\Css\Processor');
            $configVariables = $objectManager->get('\Magento\Variable\Model\Source\Variables');
            $pubDirectory = $objectManager->get('\Magento\Framework\Filesystem');
            $args = [
                $string,
                $logger,
                $escaper,
                $assetRepo,
                $scopeConfig,
                $coreVariableFactory,
                $storeManager,
                $layout,
                $layoutFactory,
                $appState,
                $urlModel,
                $configVariables,
                $variableResolver,
                $cssProcessor,
                $pubDirectory,
                $cssInliner,
                $variables
            ];
        }

        parent::__construct(...$args);

        $this->_modifiers['formatCurrency'] = [$this, 'modifierFormatCurrency'];
        $this->componentPartsCollectorFactory = $componentPartsCollectorFactory;
        $this->componentConfigResolver = $componentConfigResolver;
        $this->defaultValuesParser = $defaultValuesParser;
        $this->ampComponentLibraryJs = $ampComponentLibraryJs;
        $this->componentDataLocator = $componentDataLocator;
        $this->tokenGenerator = $tokenGenerator;
        $this->priceCurrency = $priceCurrency;
        $this->clearAmpForEmailHtml = $clearAmpForEmailHtml;
    }

    /**
     * @param array                                                        $construction
     * @param array                                                        $templateVars
     * @param \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface $componentPartsCollector
     * @return string
     */
    public function createPrampComponent(
        array $construction,
        array $templateVars,
        ComponentPartsCollectorInterface $componentPartsCollector
    ) : string {
        $paramString = preg_replace('#^_component#', '', $construction[2]);
        $params = $this->getParameters($paramString);

        // Determine what name block should have in layout
        $name = $params['name'] ?? null;

        if (isset($this->_storeId) && ! isset($params['store_id'])) {
            $params['store_id'] = $this->_storeId;
        }

        // validate required parameter type or id
        if (! empty($params['type'])) {
            $type = $params['type'];
        } else {
            return '';
        }

        // Check if component is registered
        if (! ($xmlConfigAsArray = $this->componentConfigResolver->execute($type))) {
            return '';
        }

        $defaultParams = $this->defaultValuesParser->execute($xmlConfigAsArray);

        // Define component block and check the type is instance of Component Interface
        $component = $this->_layout->createBlock($type, $name, ['data' => array_merge($defaultParams, $params)]);
        if (! $component instanceof \Magento\Widget\Block\BlockInterface) {
            return '';
        }

        if ($component instanceof \Plumrocket\AmpEmailApi\Api\ComponentInterface) {
            $component->setComponentPartsCollector($componentPartsCollector);
            $component->setEmailTemplateVars($templateVars);
        } else {
            try {
                $component->setComponentPartsCollector($componentPartsCollector);
                $component->setEmailTemplateVars($templateVars);
            } catch (\Exception $exception) {
                $this->_logger->debug($exception->getMessage());
            }
        }

        return $component->toHtml();
    }

    /**
     * Generate component
     *
     * @param string[] $construction
     * @return string
     */
    public function prampDirective($construction) : string
    {
        if (0 === strpos($construction[0], '{{pramp_component')) {
            return $this->createPrampComponent(
                $construction,
                $this->templateVars,
                $this->getPrampComponentPartsCollector()
            );
        }

        return '';
    }

    /**
     * @param int|float|string $price
     * @return float|string
     */
    public function modifierFormatCurrency($price)
    {
        return $this->priceCurrency->format($price, false);
    }

    /**
     * Add render amp components.
     * It's cannot be done simply by directive because
     * we need to add component's parts (styles, state, etc.) to head
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        $this->fillComponentDataLocator($this->templateVars);

        $filteredContent = parent::filter($value);

        $contentWithComponents = $this->addPrampComponentParts($filteredContent);
        $this->resetPrampComponentPartsCollector();
        $emailHtml = $this->addAmpComponentLibraryJs($contentWithComponents);

        return $this->clearAmpForEmailHtml->execute($emailHtml);
    }

    /**
     * @param string $filteredContent
     * @return string
     */
    private function addPrampComponentParts(string $filteredContent) : string
    {
        if ($this->getPrampComponentPartsCollector()->getCount()) {
            return $this->getPrampComponentPartsCollector()->renderIntoEmailContent($filteredContent);
        }

        return $filteredContent;
    }

    /**
     * @param string $contentWithComponents
     * @return string
     */
    private function addAmpComponentLibraryJs(string $contentWithComponents) : string
    {
        if ($libraryList = $this->ampComponentLibraryJs->detectUsedAmpComponents($contentWithComponents)) {
            return $this->ampComponentLibraryJs->renderIntoEmailContent($contentWithComponents, $libraryList);
        }

        return $contentWithComponents;
    }

    /**
     * @return \Plumrocket\AmpEmailApi\Api\ComponentPartsCollectorInterface
     */
    private function getPrampComponentPartsCollector() : ComponentPartsCollectorInterface
    {
        if (null === $this->componentPartsCollector) {
            $this->componentPartsCollector = $this->componentPartsCollectorFactory->create();
        }

        return $this->componentPartsCollector;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Template\Filter
     */
    private function resetPrampComponentPartsCollector() : self
    {
        $this->componentPartsCollector = null;
        return $this;
    }

    /**
     * @param array $emailTemplateVars
     * @return int
     */
    private function getCustomerIdFromVars(array $emailTemplateVars) : int
    {
        if (isset($emailTemplateVars['customer'])) {
            if (is_numeric($emailTemplateVars['customer'])) {
                return (int) $emailTemplateVars['customer'];
            }
            return (int) $emailTemplateVars['customer']->getId();
        }
        if (isset($emailTemplateVars['customer_id'])) {
            return (int) $emailTemplateVars['customer_id'];
        }
        if (isset($emailTemplateVars['order'])) {
            return (int) $emailTemplateVars['order']->getCustomerId();
        }

        return 0;
    }

    /**
     * @param array $emailTemplateVars
     * @return int
     */
    private function getStoreIdFromVars(array $emailTemplateVars) : int
    {
        if (isset($emailTemplateVars['store_id'])) {
            return (int) $emailTemplateVars['store_id'];
        }
        if (isset($emailTemplateVars['order'])) {
            return (int) $emailTemplateVars['order']->getStoreId();
        }

        return (int) $this->getStoreId();
    }

    /**
     * @param array $templateVars
     * @return \Plumrocket\AmpEmail\Model\Template\Filter
     */
    private function fillComponentDataLocator(array $templateVars) : self
    {
        // Customer id could be set earlier, for example in
        // \Plumrocket\AmpEmail\Plugin\Magento\ProductAlert\Model\EmailPlugin
        if (! $this->componentDataLocator->getCustomerId()) {
            $this->componentDataLocator->setCustomerId($this->getCustomerIdFromVars($templateVars));
        }

        $this->componentDataLocator->setStoreId($this->getStoreIdFromVars($templateVars));

        try {
            $token = $this->tokenGenerator->execute(
                $this->componentDataLocator->getCustomerId(),
                $this->componentDataLocator->getRecipientEmail(),
                \Plumrocket\AmpEmail\Model\Security\AmpEmailTokenType::KEY
            );
            $this->componentDataLocator->setToken($token->getHash());
        } catch (CouldNotSaveException|SecurityViolationException|LocalizedException $e) {
            $this->_logger->critical($e);
        }

        return $this;
    }
}
