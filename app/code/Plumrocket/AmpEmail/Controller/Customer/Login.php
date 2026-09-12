<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Controller\Customer;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class Login
 *
 * @since 1.0.1
 */
class Login extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory
     */
    protected $ampJsonFactory;

    /**
     * @var \Plumrocket\Token\Api\CustomerRepositoryInterface
     */
    private $tokenRepository;

    /**
     * @var \Plumrocket\Token\Api\CustomerHashValidatorInterface
     */
    private $tokenHashValidator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Plumrocket\AmpEmail\Model\LoginCustomerByIdInterface
     */
    private $loginCustomerById;

    /**
     * Login constructor.
     *
     * @param \Magento\Framework\App\Action\Context                   $context
     * @param \Magento\Store\Model\StoreManagerInterface              $storeManager
     * @param \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory        $ampJsonFactory
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface    $tokenRepository
     * @param \Plumrocket\Token\Api\CustomerHashValidatorInterface $tokenHashValidator
     * @param \Psr\Log\LoggerInterface                                $logger
     * @param \Plumrocket\AmpEmail\Model\LoginCustomerByIdInterface   $loginCustomerById
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory $ampJsonFactory,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository,
        \Plumrocket\Token\Api\CustomerHashValidatorInterface $tokenHashValidator,
        \Psr\Log\LoggerInterface $logger,
        \Plumrocket\AmpEmail\Model\LoginCustomerByIdInterface $loginCustomerById
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->ampJsonFactory = $ampJsonFactory;
        $this->tokenRepository = $tokenRepository;
        $this->tokenHashValidator = $tokenHashValidator;
        $this->logger = $logger;
        $this->loginCustomerById = $loginCustomerById;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $tokenHash = (string) $this->getRequest()->getParam('token');
        $storeId = (int) $this->getRequest()->getParam('store');

        try {
            $this->tokenHashValidator->validate($tokenHash);
            $token = $this->tokenRepository->get($tokenHash);
            $this->loginCustomerById->execute($token->getCustomerId(), $storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->critical($e);
        } catch (\Magento\Framework\Exception\ValidatorException $e) {
            $this->logger->critical('Detect request with invalid token to Plumrocket_AmpEmail auto login.');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e);
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('checkout/cart');
    }
}
