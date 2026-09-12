<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Controller\V1\ProductAlert\Unsubscribe;

class StockAll extends \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
{
    /**
     * @var \Magento\ProductAlert\Model\StockFactory
     */
    private $stockAlertFactory;

    /**
     * StockAll constructor.
     *
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Magento\Store\Model\App\Emulation                   $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory     $ampJsonFactory
     * @param \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface      $corsValidator
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository
     * @param \Magento\ProductAlert\Model\StockFactory             $stockAlertFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory $ampJsonFactory,
        \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface $corsValidator,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository,
        \Magento\ProductAlert\Model\StockFactory $stockAlertFactory
    ) {
        parent::__construct($context, $appEmulation, $storeManager, $ampJsonFactory, $corsValidator, $tokenRepository);
        $this->stockAlertFactory = $stockAlertFactory;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    public function execute()
    {
        $ampJsonResult = $this->ampJsonFactory->create();
        $this->startEmulationForAmp();

        try {
            /** @var \Magento\ProductAlert\Model\Stock $model */
            $model = $this->stockAlertFactory->create();

            $model->deleteCustomer(
                $this->getTokenModel()->getCustomerId(),
                $this->storeManager->getStore()->getWebsiteId()
            );

            $ampJsonResult->addSuccessMessage(__('You have successfully unsubscribed from all alerts.'));
        } catch (\Exception $e) {
            $ampJsonResult->addExceptionMessage($e, __('Unable to update the alert subscription. Please try again later.'));
        }

        $this->stopEmulation();
        return $ampJsonResult;
    }
}
