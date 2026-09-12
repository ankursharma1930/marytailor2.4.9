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
namespace Rootways\Authorizecim\Block\ApplePay;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\MethodInterface;
use Rootways\Authorizecim\Helper\Data;

abstract class AbstractButton extends Template
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var MethodInterface
     */
    protected $payment;

    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param MethodInterface $payment
     * @param Data $customHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        MethodInterface $payment,
        Data $customHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->payment = $payment;
        $this->customHelper = $customHelper;
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml(): string // @codingStandardsIgnoreLine
    {
        if ($this->isActive()) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->payment->isAvailable($this->checkoutSession->getQuote());
    }

    /**
     * Merchant name to display in popup
     *
     * @return string
     */
    public function getMerchantName(): string
    {
        return $this->customHelper->getDisplayName();
    }

    /**
     * Is customer logged in flag
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return $this->customHelper->isLoggedIn();
    }

    /**
     * Cart grand total
     *
     * @return float|null
     */
    public function getAmount()
    {
        return $this->checkoutSession->getQuote()->getBaseGrandTotal();
    }

    /**
     * @return bool
     */
    public function isQuoteVirtual() : bool
    {
        return $this->checkoutSession->getQuote()->getIsVirtual();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStorecode(): string
    {
        return $this->customHelper->getStoreCode();
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->customHelper->getApplepayCurrency();
    }

    /**
     * @return string
     */
    public function getMerchantCountryCode(): string
    {
        return $this->customHelper->getApplepayMerchantCountryCode();
    }

    /**
     * @return bool
     */
    public function enableInstantPurchase(): bool
    {
        return $this->isQuoteVirtual() ? false : $this->customHelper->enableInstantPurchase();
    }
}
