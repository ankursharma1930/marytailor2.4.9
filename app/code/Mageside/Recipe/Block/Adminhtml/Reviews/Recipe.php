<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Block\Adminhtml\Reviews;

class Recipe extends \Magento\Backend\Block\Template
{
    /**
     * Path to template file in theme.
     * @var string
     */
    protected $_template = 'Mageside_Recipe::review/fieldset.phtml';

    /**
     * Recipe model
     * @var \Mageside\Recipe\Model\Recipe
     */
    protected $_recipe;

    /**
     * Recipe ID
     * @var int
     */
    protected $_recipeId;

    /**
     * Core registry
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Recipe constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Mageside\Recipe\Model\Recipe $recipe
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Mageside\Recipe\Model\Recipe $recipe,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_recipe = $recipe;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Getting URL for edit recipe
     * @return bool|string
     */
    public function getRecipeUrl()
    {
        if ($this->getRecipeId()) {
            $recipeUrl = $this->_urlBuilder->getUrl('recipe/recipe/edit', ['recipe_id' => $this->getRecipeId()]);

            return $recipeUrl;
        }

        return false;
    }

    /**
     * Getting recipe name
     * @return bool|string
     */
    public function getRecipeName()
    {
        if ($this->getRecipeId()) {
            $model = $this->_recipe->load($this->getRecipeId());

            if (!empty($model->getData())) {
                $recipeName = $model->getTitle();

                return $recipeName;
            }
        }

        return false;
    }

    /**
     * Getting recipe ID for later using
     * @return bool|int
     */
    protected function getRecipeId()
    {
        if (!$this->_recipeId) {
            $this->_recipeId = false;
            if ($this->_coreRegistry->registry('review_data')) {
                $this->_recipeId = $this->_coreRegistry->registry('review_data')->getEntityPkValue();
            } elseif ($this->_coreRegistry->registry('new_review_data')) {
                $this->_recipeId = $this->_coreRegistry->registry('new_review_data')->getEntityPkValue();
            }
        }

        return $this->_recipeId;
    }
}
