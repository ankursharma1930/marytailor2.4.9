<?php
declare(strict_types=1);

namespace Dolphin\Productfaq\Observer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\RequestHandlerInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Dolphin\Productfaq\Helper\ProductFaqHelper;

class RecapchaFormObserver implements ObserverInterface
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var bool
     */
    private $isCaptchaEnabled;

    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;

    /**
     * Constructor function to initialize dependencies.
     *
     * @param UrlInterface              $url
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param RequestHandlerInterface   $requestHandler
     * @param RedirectInterface         $redirect
     * @param ProductFaqHelper          $helper
     */
    public function __construct(
        UrlInterface $url,
        IsCaptchaEnabledInterface $isCaptchaEnabled,
        RequestHandlerInterface $requestHandler,
        RedirectInterface $redirect,
        ProductFaqHelper $helper
    ) {
        $this->url = $url;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
        $this->requestHandler = $requestHandler;
        $this->redirect = $redirect;
        $this->helper = $helper;
    }

    /**
     * Executes the observer logic to handle reCAPTCHA verification.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $key = 'productfaq_recaptcha_frontend';
        $enable_captcha = $this->helper->getReCaptchaStatus();
        if (($enable_captcha == 1)) {
            if ($this->isCaptchaEnabled->isCaptchaEnabledFor($key)) {
                /** @var Action $controller */
                $controller = $observer->getControllerAction();
                $request = $controller->getRequest();
                $response = $controller->getResponse();
                $redirectOnFailureUrl = $this->redirect->getRedirectUrl();
                $this->requestHandler->execute($key, $request, $response, $redirectOnFailureUrl);
            }
        }
    }
}
