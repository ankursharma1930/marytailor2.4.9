<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogPlus\Block\Adminhtml\Config\Form;

use Magefan\BlogPlus\Model\Facebook\Publisher;

/**
 * Class Info
 */
class LoginUrl extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magefan\BlogPlus\Model\Facebook\Publisher $publisher,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->publisher = $publisher;
    }

    /**
     * Return info block html
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($url = $this->getLoginToFbUrl()) {
            $callbackUrl = $this->getCallbackUrl();
            if (false === strpos($callbackUrl, 'https://')) {
                $url = $this->getUrl('adminhtml/system_config/edit', ['section' => 'web']);
                $html = '<div style="padding:10px;background-color:#f8f8f8;border:1px solid #ddd;margin-bottom:7px;">' .
                    ( ($this->publisher->getConfig()->getFbAccessToken())
                        ? __('<strong>Facebook Access Token:</strong> generated') . '<br/>'
                        : __('<strong style="color:red">Attantion! Facebook Access Token is not generated.</strong>')) . '<br/>' .
                    __('To generate access tocket, please <a href="%1" target="_blank">enable HTTPS for admin urls</a> first.', $url) .
                '</div>';
            } else {
                $html = '<div style="padding:10px;background-color:#f8f8f8;border:1px solid #ddd;margin-bottom:7px;">' .
                    __('<strong>Valid OAuth Redirect URI:</strong> %1', $callbackUrl) . '<br/>' .
                    ( ($this->publisher->getConfig()->getFbAccessToken())
                        ? __('<strong>Facebook Access Token:</strong> Generated') . '<br/>'
                        : __('<strong style="color:red">Attantion! Facebook Access Token is not generated.</strong>')) . '<br/>' .
                    __('Please <a href="%1" target="_blank" >click here</a> to generate a new Facebook Access Token.', $url) .
                '</div>';
            }
        } else {
            $html = '<div style="padding:10px;background-color:#f8f8f8;border:1px solid #ddd;margin-bottom:7px;">' .
            __('Please enter <strong>Facebook App ID</strong>, <strong>Facebook App Secret</strong>, <strong>Facebook Page ID</strong> and save the config to get access token.') . ' ' .
                __('<a href="%1" target="_blank">Click here</a> to learn how to create new Facebook App.', 'https://developers.facebook.com/docs/apps') .
            '</div>';
        }

        return $html;
    }

    /**
     * @return bool
     */
    protected function getLoginToFbUrl()
    {
        if ($fb = $this->publisher->getFbApi()) {
            $helper = $fb->getRedirectLoginHelper();
            $callback = $this->getCallbackUrl();
            $permissions = [ 'pages_manage_posts'];
            return $helper->getLoginUrl($callback, $permissions);
        }
        return false;
    }

    protected function getCallbackUrl()
    {
        return $this->getUrl('blogplus/accesstoken/longlived', [
            \Magento\Backend\Model\Url::SECRET_KEY_PARAM_NAME => false
        ]);
    }
}
