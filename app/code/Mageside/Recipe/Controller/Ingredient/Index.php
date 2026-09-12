<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Ingredient;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Mageside\Recipe\Model\RecipeFactory;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Mageside\Recipe\Controller\Ingredient
 */
class Index extends Action
{
    /** @var RecipeFactory */
    public $recipeFactory;

    /** @var Registry */
    public $coreRegistry;

    /** @var  PageFactory*/
    public $pageFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param RecipeFactory $recipeFactory
     * @param PageFactory $pageFactory
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        RecipeFactory $recipeFactory,
        PageFactory $pageFactory,
        Registry $coreRegistry
    ) {
        $this->recipeFactory = $recipeFactory;
        $this->pageFactory = $pageFactory;
        $this->coreRegistry = $coreRegistry;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        return $resultPage;
    }
}
