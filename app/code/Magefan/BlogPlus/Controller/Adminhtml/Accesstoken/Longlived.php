<?php

/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Controller\Adminhtml\Accesstoken;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magefan\BlogPlus\Model\Facebook\Publisher;

/**
 * Class Longlived
 */
class Longlived extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magefan_Blog::config_blog';

    /**
     * Array of actions which can be processed without secret key validation
     *
     * @var array
     */
    protected $_publicActions = ['longlived'];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Config\Model\Config
     */

    protected $config;

    /**
     * @var Publisher $publisher
     */
    protected $publisher;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Config\Model\Config $config,
        Publisher $publisher
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Filter request params
     * @param  array $data
     * @return array
     */
    public function execute()
    {
        try {
            $fb = $this->publisher->getFbApi();

            $helper = $fb->getRedirectLoginHelper();
            $state = $this->getRequest()->getParam('state');

            foreach (['state', 'code'] as $key) {
                $value = $this->getRequest()->getParam($key);
                if ($value) {
                    $helper->getPersistentDataHandler()->set($key, $value);
                }
            }

            $accessToken = $helper->getAccessToken(
                $this->_url->getUrl('blogplus/accesstoken/longlived', [
                    \Magento\Backend\Model\Url::SECRET_KEY_PARAM_NAME => false
                ])
            );

            if (!isset($accessToken)) {
                if ($helper->getError()) {
                    throw new \Exception(
                        "Error: " . $helper->getError() . PHP_EOL .
                        "Error Code: " . $helper->getErrorCode() . PHP_EOL.
                        "Error Reason: " . $helper->getErrorReason() . PHP_EOL .
                        "Error Description: " . $helper->getErrorDescription() . PHP_EOL
                    );
                } else {
                    throw new \Exception(__('Bad Facebook request'));
                }
            } else {
                $oAuth2Client = $fb->getOAuth2Client();
                $tokenMetadata = $oAuth2Client->debugToken($accessToken);
                $tokenMetadata->validateAppId($this->publisher->getConfig()->getFbAppId());
                $tokenMetadata->validateExpiration();

                if (!$accessToken->isLongLived()) {
                    try {
                        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                    } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                        throw new \Exception('Error getting long-lived access token: ' . $helper->getMessage());
                    }
                }

                $longLivedToken = $accessToken->getValue();
                $fb->setDefaultAccessToken($longLivedToken);
                $response = $fb->sendRequest('GET', $this->publisher->getConfig()->getFbPageId(), ['fields' => 'access_token'])
                    ->getDecodedBody();

                $accessToken = isset($response['access_token']) ? $response['access_token'] : null;
                if (!$accessToken) {
                    throw new \Exception(__('Facebook Access Tocket is empty or missing'), 1);
                }
                $this->saveAccessToken($accessToken);

                $this->messageManager->addSuccess(__('Facebook Access Tocket has been generated and saved successfully.'));
            }
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $this->messageManager->addError(
                'Facebook Graph returned an error: ' . $e->getMessage()
            );
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $this->messageManager->addError(
                'Facebook SDK returned an error: ' . $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->messageManager->addError(nl2br($e->getMessage()));
        }

        $this->_redirect('adminhtml/system_config/edit', ['section' => 'mfblog']);
    }

    /**
     * @return null
     */
    protected function saveAccessToken($value)
    {
        $this->config->setDataByPath(
            \Magefan\BlogPlus\Model\Config::XML_PATH_TO_FB_ACCESS_TOKEN,
            $value
        );
        $this->config->save();
    }
}
