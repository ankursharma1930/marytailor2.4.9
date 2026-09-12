<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_AmpEmailApi
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmailApi\Controller;

use Magento\Framework\App\Area;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

abstract class AbstractStoreViewAction extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Plumrocket\AmpEmailApi\Model\Result\AmpJsonFactoryInterface
     */
    protected $ampJsonFactory;

    /**
     * @var \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface
     */
    private $corsValidator;

    /**
     * @var \Plumrocket\Token\Api\CustomerRepositoryInterface
     */
    private $tokenRepository;

    /**
     * AbstractByStoreAction constructor.
     *
     * @param \Magento\Framework\App\Action\Context                        $context
     * @param \Magento\Store\Model\App\Emulation                           $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Plumrocket\AmpEmailApi\Model\Result\AmpJsonFactoryInterface $ampJsonFactory
     * @param \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface         $corsValidator
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface            $tokenRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmailApi\Model\Result\AmpJsonFactoryInterface $ampJsonFactory,
        \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface $corsValidator,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository
    ) {
        parent::__construct($context);
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->ampJsonFactory = $ampJsonFactory;
        $this->corsValidator = $corsValidator;
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * Emulate requested store
     *
     * @param null|string $area
     * @return \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
     */
    protected function startEmulationForAmp($area = null) : \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
    {
        $this->appEmulation->startEnvironmentEmulation(
            $this->getStoreId(),
            $area ?? Area::AREA_FRONTEND,
            true
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function stopEmulation() : self
    {
        $this->appEmulation->stopEnvironmentEmulation();
        return $this;
    }

    /**
     * Don't need check if module enabled, cors validator will do this
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface|\Plumrocket\AmpEmailApi\Model\Result\AmpJsonInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if ($request instanceof HttpRequest) {
            try {
                $this->corsValidator->execute($request);
                // TODO: refactor code after left support 2.2
                // Change exception type on \Magento\Framework\App\Request\InvalidRequestException
            } catch (\Magento\Framework\Exception\RuntimeException $exception) {
                return $this->ampJsonFactory->create()->addExceptionMessage($exception, __('Invalid CORS request.'));
            }
        }

        return parent::dispatch($request);
    }

    /**
     * Cors validation should be performed before execute method
     * In this case token will exists
     *
     * @return \Plumrocket\Token\Api\Data\CustomerInterface
     */
    public function getTokenModel() : \Plumrocket\Token\Api\Data\CustomerInterface
    {
        $tokenHash = (string) $this->getRequest()->getParam('token');
        try {
            return $this->tokenRepository->get($tokenHash);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false; // This happen only if somebody disable CORS Validation
        }
    }

    /**
     * @return int
     */
    protected function getStoreId() : int
    {
        return (int) $this->getRequest()->getParam('store');
    }

    /**
     * Disable validation in this point.
     * Validation realized in "dispatch" method because we should send special response for amp
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool|null
     * @see \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction::dispatch
     *
     */
    public function validateForCsrf(\Magento\Framework\App\RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return \Magento\Framework\App\Request\InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}
