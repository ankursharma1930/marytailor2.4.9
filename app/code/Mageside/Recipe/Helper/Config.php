<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Helper;

/**
 * Class Config
 */
class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var int
     */
    public $currentStoreId;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * Get module settings
     *
     * @param $key
     * @param $section
     *
     * @return mixed
     */
    public function getConfigModule($key, $section = 'general')
    {
        return $this->scopeConfig
            ->getValue(
                "mageside_recipe/$section/$key",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->getConfigModule('enabled') && $this->isModuleOutputEnabled('Mageside_Recipe')) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function googleRichSnippets()
    {
        if ($this->getConfigModule('google_rich_snippets')) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isAjaxEnabled()
    {
        if ($this->getConfigModule('load_by_ajax', 'recipe_list')) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getSeoRoute()
    {
        return trim($this->getConfigModule('route', 'seo'), "/");
    }

    /**
     * @return mixed
     */
    public function getSeoTitle()
    {
        return $this->getConfigModule('page_title_for_recipe', 'seo');
    }

    /**
     * @return mixed
     */
    public function getSeoPostfix()
    {
        return $this->getConfigModule('url_postfix', 'seo');
    }

    /**
     * Recipe listing page URL, e.g. https://example.com/recipe
     *
     * @return string
     */
    public function getRecipeListUrl()
    {
        return $this->_urlBuilder->getDirectUrl($this->getSeoRoute());
    }

    /**
     * Single recipe page URL, e.g. https://example.com/recipe/beeswax-lip-balm
     *
     * Controller\Router only matches "<route>/<url_key>", and getUrl($route) has no
     * trailing slash, so the two must be joined explicitly.
     *
     * @param \Mageside\Recipe\Model\Recipe $recipe
     * @return string
     */
    public function getRecipeUrl($recipe)
    {
        return $this->_urlBuilder->getDirectUrl(
            $this->getSeoRoute() . '/' . $recipe->getUrlKey() . $this->getSeoPostfix()
        );
    }

    /**
     * Writer page URL, or an empty string when the writer has no routable page
     *
     * Controller\Router redirects non-writers away, and it cannot match a url key
     * containing slashes (some writers have a full URL stored as their key).
     *
     * @param \Mageside\Recipe\Model\Writer $writer
     * @return string
     */
    public function getWriterUrl($writer)
    {
        $urlKey = (string) $writer->getWriterUrlKey();
        if (!$writer->getIsWriter() || !preg_match('/^[a-z0-9_-]+$/i', $urlKey)) {
            return '';
        }

        return $this->_urlBuilder->getDirectUrl($this->getSeoRoute() . '/' . $urlKey);
    }

    /**
     * @return mixed
     */
    public function getProductBlockTitle()
    {
        return $this->getConfigModule('block_title', 'product_page');
    }

    /**
     * @return mixed
     */
    public function getRecipesPerPage()
    {
        return $this->getConfigModule('recipe_per_page', 'recipe_list');
    }

    /**
     * @return mixed
     */
    public function getSocialShareEnable()
    {
        return $this->getConfigModule('social_share_enable', 'recipe_list');
    }

    /**
     * @return mixed
     */
    public function getFacebookShare()
    {
        return $this->getConfigModule('facebook_share', 'recipe_list');
    }

    /**
     * @return mixed
     */
    public function getTwitterShare()
    {
        return $this->getConfigModule('twitter_share', 'recipe_list');
    }

    /**
     * @return mixed
     */
    public function getGoogleShare()
    {
        return $this->getConfigModule('google_share', 'recipe_list');
    }

    /**
     * @return mixed
     */
    public function getPinterestShare()
    {
        return $this->getConfigModule('pinterest_share', 'recipe_list');
    }

    /**
     * @return mixed
     */
    public function getShowOnProductPage()
    {
        return $this->getConfigModule('show_on_product_page', 'product_page');
    }

    /**
     * @return mixed
     */
    public function getRecipesPerProductPage()
    {
        return $this->getConfigModule('recipes_per_page', 'product_page');
    }

    /**
     * @return mixed
     */
    public function getReviewEnabled()
    {
        return $this->getConfigModule('enabled', 'review');
    }

    /**
     * @return mixed
     */
    public function getIsGuestAllowToWrite()
    {
        return $this->getConfigModule('availability', 'review');
    }

    /**
     * @param $data
     * @return mixed
     */
    public function prepareSlug($data)
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower(str_replace(' ', '_', trim($data))));
    }

    /**
     * @return mixed
     */
    public function getRecipesPerIngredientPageTitle()
    {
        return $this->getConfigModule('recipes_per_ingredient_page_title', 'recipe_ingredient');
    }

    /**
     * @return mixed
     */
    public function getIngredientPageTitle()
    {
        return $this->getConfigModule('ingredient_page_title', 'recipe_ingredient');
    }

    /**
     * @return mixed
     */
    public function getStoreIdChecked()
    {
        return $this->currentStoreId;
    }

    /**
     * @param $currentStoreId
     * @return mixed
     */
    public function setStoreIdChecked($currentStoreId)
    {
        $this->currentStoreId = $currentStoreId;
        return $this->currentStoreId;
    }
}
