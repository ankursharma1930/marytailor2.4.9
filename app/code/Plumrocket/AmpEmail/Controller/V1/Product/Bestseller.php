<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Controller\V1\Product;

class Bestseller extends \Plumrocket\AmpEmailApi\Controller\AbstractStoreViewAction
{

    /**
     * @var \Plumrocket\AmpEmail\ViewModel\Component\Product\CarouselDataExtractor
     */
    private $carouselDataExtractor;

    /**
     * @var \Plumrocket\Base\Api\ExtensionStatusInterface
     */
    private $extensionStatus;

    /**
     * Bestseller constructor.
     *
     * @param \Magento\Framework\App\Action\Context                                  $context
     * @param \Magento\Store\Model\App\Emulation                                     $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface                             $storeManager
     * @param \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory                       $ampJsonFactory
     * @param \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface                   $corsValidator
     * @param \Plumrocket\Token\Api\CustomerRepositoryInterface                      $tokenRepository
     * @param \Plumrocket\AmpEmail\ViewModel\Component\Product\CarouselDataExtractor $carouselDataExtractor
     * @param \Plumrocket\Base\Api\ExtensionStatusInterface                          $extensionStatus
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\AmpEmail\Model\Result\AmpJsonFactory $ampJsonFactory,
        \Plumrocket\AmpEmailApi\Model\CorsValidatorInterface $corsValidator,
        \Plumrocket\Token\Api\CustomerRepositoryInterface $tokenRepository,
        \Plumrocket\AmpEmail\ViewModel\Component\Product\CarouselDataExtractor $carouselDataExtractor,
        \Plumrocket\Base\Api\ExtensionStatusInterface $extensionStatus
    ) {
        parent::__construct($context, $appEmulation, $storeManager, $ampJsonFactory, $corsValidator, $tokenRepository);
        $this->carouselDataExtractor = $carouselDataExtractor;
        $this->extensionStatus = $extensionStatus;
    }

    /**
     * @return \Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    public function execute()
    {
        $this->startEmulationForAmp();

        $productsInfo = [];
        if ($this->extensionStatus->isEnabled('Plumrocket_Bestsellers')) {
            $customerId = $this->getTokenModel()->getCustomerId();

            $productCount = (int) $this->getRequest()->getParam('count');
            $period = (string) $this->getRequest()->getParam('period', 'month');

            /** @var \Plumrocket\Bestsellers\Api\ProductIdsProviderInterface $bestsellersProvider */
            $bestsellersProvider = $this->_objectManager
                ->get('\Plumrocket\Bestsellers\Api\ProductIdsProviderInterface'); //@codingStandardsIgnoreLine

            $productIds = $bestsellersProvider->getByPeriod($period, $productCount ?: 10);
            if ($productIds) {
                $productsInfo = $this->carouselDataExtractor->execute($productIds, $customerId);
            }
        }

        $this->stopEmulation();

        return $this->ampJsonFactory->create()->setData(['products' => $productsInfo]);
    }
}
