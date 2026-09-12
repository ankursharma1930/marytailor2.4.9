<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Block\Widget;

/**
 * @api
 * @since 100.0.2
 */
class Recipe extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addChildBlock()
    {
        $block = $this->getLayout()
            ->createBlock(\Mageside\Recipe\Block\Frontend\Recipe\Widget::class)
            ->setData($this->getData())
            ->setTemplate("Mageside_Recipe::recipe/list/items.phtml");

        $this->setChild("recipe_items", $block);
    }

    /**
     * Retrieve block title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getData('title') ? $this->getData('title') : "Recipes";
    }

    /**
     * @inheritDoc
     */
    public function toHtml()
    {
        $this->addChildBlock();

        return parent::toHtml();
    }
}
