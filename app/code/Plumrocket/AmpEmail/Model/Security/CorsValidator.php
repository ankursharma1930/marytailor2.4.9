<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Security;

class CorsValidator implements \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface
{
    /**
     * @var array
     */
    private $productionOrigins = ['https://mail.google.com'];

    /**
     * @var array
     */
    private $testOrigins = ['https://amp.gmail.dev'];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Plumrocket\AmpEmail\Helper\Config
     */
    private $configHelper;

    /**
     * @var \Plumrocket\Token\Api\CustomerHashValidatorInterface
     */
    private $tokenHashValidator;

    /**
     * @var \Plumrocket\AmpEmail\Api\IsVerifiedSenderInterface
     */
    private $isVerifiedSender;

    /**
     * @var \Plumrocket\AmpEmail\Helper\Config
     */
    private $config;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Plumrocket\AmpEmail\Helper\Config                   $configHelper
     * @param \Plumrocket\Token\Api\CustomerHashValidatorInterface $tokenHashValidator
     * @param \Plumrocket\AmpEmail\Api\IsVerifiedSenderInterface   $isVerifiedSender
     * @param \Plumrocket\AmpEmail\Helper\Config                   $config
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Helper\Config $configHelper,
        \Plumrocket\Token\Api\CustomerHashValidatorInterface $tokenHashValidator,
        \Plumrocket\AmpEmail\Api\IsVerifiedSenderInterface $isVerifiedSender,
        \Plumrocket\AmpEmail\Helper\Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->tokenHashValidator = $tokenHashValidator;
        $this->isVerifiedSender = $isVerifiedSender;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\App\Request\Http $request) : bool
    {
        $storeId = (int) $request->getParam('store');
        $sourceOrigin = (string) $request->getParam('__amp_source_origin');
        $origin = (string) $request->getHeader('Origin');
        $tokenHash = (string) $request->getParam('token');

        try {
            $this->storeManager->getStore($storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $noSuchEntityException) {
            throw new \Magento\Framework\Exception\RuntimeException(
                __('AmpEmail::Invalid store id "' . $storeId . '".')
            );
        }

        if (! $this->config->isModuleEnabled($storeId)) {
            throw new \Magento\Framework\Exception\RuntimeException(
                __('AmpEmail::AMP For Email extension is disabled')
            );
        }

        try {
            $this->validateAmpSourceOriginHeader($sourceOrigin, $storeId);
            $this->validateOriginHeader($origin, $storeId);
            $this->tokenHashValidator->validate($tokenHash);
        } catch (\Magento\Framework\Exception\ValidatorException $e) {
            throw new \Magento\Framework\Exception\RuntimeException(__($e->getMessage()));
        }

        return true;
    }

    /**
     * @param string $sourceOrigin
     * @param int    $storeId
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function validateAmpSourceOriginHeader(string $sourceOrigin, int $storeId)
    {
        $isVerified = $this->isVerifiedSender->execute(
            $sourceOrigin,
            $this->configHelper->isAllowRequestFromAmpPlayground($storeId)
        );

        if (! $isVerified) {
            throw new \Magento\Framework\Exception\RuntimeException(
                __('Amp Source Origin ' . $sourceOrigin . ' is disallowed.')
            );
        }
    }

    /**
     * @param string $origin
     * @param int    $storeId
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function validateOriginHeader(string $origin, int $storeId)
    {
        if ($this->configHelper->isAllowRequestFromAmpPlayground($storeId)) {
            $allowedOrigins = array_merge($this->productionOrigins, $this->testOrigins);
        } else {
            $allowedOrigins = $this->productionOrigins;
        }

        if (! in_array($origin, $allowedOrigins, true)) {
            throw new \Magento\Framework\Exception\RuntimeException(
                __('Origin ' . $origin . ' isn\'t allowed.')
            );
        }
    }
}
