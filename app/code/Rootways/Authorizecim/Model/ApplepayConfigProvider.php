<?php
/**
 * Authorize.net Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;

/**
 * Class ApplepayConfigProvider
 */
class ApplepayConfigProvider implements ConfigProviderInterface
{
    const APPLE_PAY_CODE = 'rootways_authorizecim_option_applepay';

    protected $methodCodes = [
        self::APPLE_PAY_CODE
    ];

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @param \Rootways\Authorizecim\Helper\Data $customHelper
     * @param Repository $assetRepo
     */
    public function __construct(
        \Rootways\Authorizecim\Helper\Data $customHelper,
        Repository $assetRepo
    ) {
        $this->dataHelper = $customHelper;
        $this->assetRepo = $assetRepo;
    }

    public function getPaymentMarkSrc()
    {
        return $this->assetRepo->getUrl('Rootways_Authorizecim::images/applepaytitle.png');
    }

    protected function getApplePayCcAvailableCcTypes()
    {
        return $this->dataHelper->getApplePayCcAvailableCardTypes();
    }

    protected function getMerchantIdentifier()
    {
        $storeId = $this->dataHelper->getStoreId();
        return $this->dataHelper->getMerchantIdentifier($storeId);
    }

    protected function getDisplayName()
    {
        $storeId = $this->dataHelper->getStoreId();
        return $this->dataHelper->getDisplayName($storeId);
    }

    protected function getApplepayCurrency()
    {
        $storeId = $this->dataHelper->getStoreId();
        return $this->dataHelper->getApplepayCurrency($storeId);
    }

    protected function getApplepayMerchantCountryCode()
    {
        $storeId = $this->dataHelper->getStoreId();
        return $this->dataHelper->getApplepayMerchantCountryCode($storeId);
    }

    /**
     * Retrieve config object
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::APPLE_PAY_CODE => [
                    'clientToken' => '',
                    'merchantIdentifier' => $this->getMerchantIdentifier(),
                    'merchantName' => $this->getDisplayName(),
                    'paymentMarkSrc' => $this->getPaymentMarkSrc(),
                    'applePayCcAvailableCcTypes' => $this->getApplePayCcAvailableCcTypes(),
                    //'isActive' => $this->dataHelper->isApplePayActive(),
                    'applepayCurrency' =>  $this->getApplepayCurrency(),
                    'applepayMerchantCountryCode' =>  $this->getApplepayMerchantCountryCode()
                ],
            ]
        ];
    }
}
