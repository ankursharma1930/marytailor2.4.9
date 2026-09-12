<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Model;

/**
 * Blog Plus url model
 */
class Url extends \Magefan\Blog\Model\Url
{
    /**
     * Retrieve blog base url
     * @return string
     */
    public function getBaseUrl()
    {
        if (!$this->isAdvancedPermalinkEnabled()) {
            return parent::getBaseUrl();
        }
        return $this->_url->getUrl('', [
            '_direct' => $this->getBasePath(),
            '_nosid' => $this->storeId ?: null
        ]);
    }

    /**
     * Retrieve blog base path
     * @return string
     */
    public function getBasePath()
    {
        if (!$this->isAdvancedPermalinkEnabled()) {
            return parent::getBasePath();
        }

        $path = $this->getAdvencedConfig('blog_route');
        return $path;
    }

    /**
     * Retrieve blog url path
     * @param  string $identifier
     * @param  string $controllerName
     * @return string
     */
    public function getUrlPath($identifier, $controllerName)
    {
        if (!$this->isAdvancedPermalinkEnabled()) {
            return parent::getUrlPath($identifier, $controllerName);
        }

        $urlPath = $this->getAdvencedConfig($controllerName . '_path_schema');

        $vars = ['blog_route', 'parent_category', 'url_key', 'id', 'year', 'month'];
        $values = [];
        foreach ($vars as $var) {
            $schemaVar = '{{' . $var . '}}';
            if (false !== strpos($urlPath, $schemaVar)) {
                if (!isset($values[$var])) {
                    switch ($var) {
                        case 'id':
                            $values[$var] = is_object($identifier) ? $identifier->getId() : '';
                            break;
                        case 'year':
                            if (self::CONTROLLER_POST == $controllerName) {
                                $values[$var] = is_object($identifier) ? $identifier->getPublishDate('Y') : '';
                            } else {
                                $values[$var] = '';
                            }
                            break;
                        case 'month':
                            if (self::CONTROLLER_POST == $controllerName) {
                                $values[$var] = is_object($identifier) ? $identifier->getPublishDate('m') : '';
                            } else {
                                $values[$var] = '';
                            }
                            break;
                        case 'blog_route':
                            $values[$var] = $this->getAdvencedConfig('blog_route');
                            break;
                        case 'url_key':
                            $values[$var] = is_object($identifier) ? $identifier->getIdentifier() : $identifier;
                            break;
                        case 'parent_category':
                            if (is_object($identifier)) {
                                $object = $identifier;
                                $parentCategoryIdentifier = '';
                                while ($parent = $object->getParentCategory()) {
                                    $parentCategoryIdentifier = $parent->getIdentifier() . '/' . $parentCategoryIdentifier;
                                    $object = $parent;
                                }
                                $parentCategoryIdentifier = trim($parentCategoryIdentifier, '/');
                                $values[$var] = $parentCategoryIdentifier;
                            } else {
                                $values[$var] = '';
                            }
                            break;
                    }
                }

                $urlPath = str_replace($schemaVar, empty($values[$var]) ? '' : $values[$var], ltrim($urlPath, '/'));
                $urlPath = str_replace('//', '/', $urlPath);
            }
        }

        return $urlPath;
    }

    /**
     * @return array
     */
    public function getPathChemas()
    {
        $controllers = ['post', 'category', 'tag', 'author', 'archive', 'search', 'rss'];
        $result = [];
        foreach ($controllers as $controller) {
            $result[$controller] = $this->getAdvencedConfig(
                $controller . '_path_schema'
            );
        }

        return $result;
    }

    /**
     * Retrieve post url sufix
     * @return string
     */
    public function getUrlSufix($controllerName)
    {
        if (!$this->isAdvancedPermalinkEnabled()) {
            return parent::getUrlSufix($controllerName);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isAdvancedPermalinkEnabled()
    {
        return (bool)$this->getAdvencedConfig('enabled');
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function getAdvencedConfig($key)
    {
        return $this->_scopeConfig->getValue(
            'mfblog/advanced_permalink/'.$key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }
}
