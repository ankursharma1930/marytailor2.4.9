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

namespace Rootways\Authorizecim\Controller\ApplePay;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\Result\PageFactory;
use Rootways\Authorizecim\Helper\Data;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param Context $context
     * @param Data $customHelper
     * @param Http $request
     * @param RawFactory $resultRawFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Data $customHelper,
        Http $request,
        RawFactory $resultRawFactory,
        PageFactory $resultPageFactory
    ) {
        $this->customHelper = $customHelper;
        $this->request = $request;
        $this->resultRawFactory = $resultRawFactory;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $crtPath = $this->customHelper->getMediaPath() . 'rootways/authorizecim/';
        $crtFileName = $this->customHelper->getConfig('payment/rootways_authorizecim_option_applepay/rw_applepay_cert');
        if (empty($crtFileName)) {
            $crtPath = $this->customHelper->getRwModuleDir().'/certs/';
            $crtFileName = $this->customHelper->getEnvironment() == 'production' ? 'apple-pay-live-cert.pem' : 'apple-pay-test-cert.pem';
        }

        $VERIFYPEER = 'false';
        $VERIFYHOST = 'false';
        if ($this->customHelper->getEnvironment() == 'production') {
            $VERIFYPEER = 1;
            $VERIFYHOST = 2;
        }

        $validationUrl = $this->request->getParam('validationUrl') == '' ?
            "https://apple-pay-gateway-cert.apple.com/paymentservices/startSession" :
            $this->request->getParam('validationUrl');
        $pemPwd = '';
        $storeId = $this->customHelper->getStoreId();
        $merchantIdentifier = $this->customHelper->getMerchantIdentifier($storeId);
        $domainName = $this->customHelper->getDomainName($storeId);
        $displayName = $this->customHelper->getDisplayName($storeId);

        $validationPayload = '{"merchantIdentifier": "'.$merchantIdentifier.'","domainName": "'.$domainName.'","displayName":"'.$displayName.'"}';
        $resultData = '';
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $validationUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $validationPayload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $VERIFYPEER);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $VERIFYHOST);
            curl_setopt($ch, CURLOPT_SSLCERT, $crtPath.$crtFileName);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $pemPwd);
            $content = curl_exec($ch);
            if (false === $content) {
                $resultData = '';
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }
            curl_close($ch);
            $resultData = $content;
        } catch (\Exception $e) {
            $resultData = '';
            trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
        }
        $result = $this->resultRawFactory->create();
        $result->setContents($resultData);

        return $result;
    }
}
