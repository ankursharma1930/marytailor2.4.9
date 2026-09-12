<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Controller\V1\Product\Actual;

class Info extends \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
{
    /**
     * @var \Plumrocket\AmpEmail\ViewModel\Component\Product\ExtractActualData
     */
    private $extractActualData;

    /**
     * Info constructor.
     *
     * @param \Magento\Framework\App\Action\Context                              $context
     * @param \Magento\Store\Model\App\Emulation                                 $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface                         $storeManager
     * @param \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory                   $ampJsonFactory
     * @param \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface                    $corsValidator
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface               $tokenRepository
     * @param \Plumrocket\AmpEmail\ViewModel\Component\Product\ExtractActualData $extractActualData
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory $ampJsonFactory,
        \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface $corsValidator,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository,
        \Plumrocket\AmpEmail\ViewModel\Component\Product\ExtractActualData $extractActualData
    ) {
        parent::__construct($context, $appEmulation, $storeManager, $ampJsonFactory, $corsValidator, $tokenRepository);
        $this->extractActualData = $extractActualData;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    public function execute()
    {
        $ampJsonResult = $this->ampJsonFactory->create();

        $this->startEmulationForAmp();

        try {
            $productId = (int) $this->getRequest()->getParam('product');
            $customerId = $this->getTokenModel()->getCustomerId();
            $ampJsonResult->setData($this->extractActualData->execute($productId, $customerId));
            $ampJsonResult->setIsSingleListItem(true);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $ampJsonResult->addExceptionMessage($e);
        }

        $this->stopEmulation();

        return $ampJsonResult;
    }
}
