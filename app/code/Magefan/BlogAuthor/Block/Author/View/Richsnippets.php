<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */
namespace Magefan\BlogAuthor\Block\Author\View;

use Magento\Store\Model\ScopeInterface;

/**
 * Blog post view rich snippets
 */
class Richsnippets extends \Magefan\BlogAuthor\Block\Author\AbstractAuthor
{
    /**
     * @param  array
     */
    protected $_options;

    /**
     * Retrieve snipet params
     *
     * @return array
     */
    public function getOptions()
    {
        if ($this->_options === null) {
            $this->_options = $this->getAuthorOptions();
        }

        return $this->_options;
    }

    /**
     * Retrieve author name
     *
     * @return array
     */
    public function getAuthorOptions()
    {
        if ($author = $this->getAuthor()) {
            if ($author->getTitle()) {
                $authorPageEnabled = $this->config->getConfig(
                    'mfblog/author/page_enabled'
                );

                $result = [
                    '@context' => 'http://schema.org',
                    '@type' => 'Person',
                    'name' => $author->getTitle(),
                    'description' => $author->getShortFilteredContent() ?: '',
                    'url' => $authorPageEnabled ? $author->getAuthorUrl() : $this->getUrl(),
                    'mainEntityOfPage' => [
                        '@id' => $authorPageEnabled ? $author->getAuthorUrl() : $this->getUrl(),
                    ]
                ];

                $sameAs = [];
                foreach (['facebook_page_url', 'twitter_page_url', 'instagram_page_url', 'googleplus_page_url', 'linkedin_page_url'] as $key) {
                    if ($value = trim($author->getData($key) ?: '')) {
                        $sameAs[] = $value;
                    }
                }

                if ($sameAs) {
                    $result['sameAs'] = $sameAs;
                }

                if ($value = trim($author->getData('role') ?: '')) {
                    $result['jobTitle'] = $value;
                }

                if ($value = $author->getFeaturedImage()) {
                    $result['image'] = $value;
                }

                return $result;
            }
        }

        // if no author name return name of publisher
        return $this->getPublisher();
    }

    /**
     * Retrieve publisher name
     *
     * @return array
     */
    public function getPublisher()
    {
        $publisherName =  $this->_scopeConfig->getValue(
            'general/store_information/name',
            ScopeInterface::SCOPE_STORE
        );

        if (!$publisherName) {
            $publisherName = 'Magento2 Store';
        }

        return $publisherName;
    }

    /**
     * Render html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        return '<script type="application/ld+json">'
            . json_encode($this->getOptions())
            . '</script>';
    }
}