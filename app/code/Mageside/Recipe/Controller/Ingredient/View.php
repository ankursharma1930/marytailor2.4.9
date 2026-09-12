<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Ingredient;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Mageside\Recipe\Model\RecipeFactory;
use Magento\Framework\View\Result\PageFactory;

class View extends Action
{
    /**  @var RecipeFactory */
    public $recipeFactory;

    /**  @var Registry */
    public $coreRegistry;

    /** @var  PageFactory*/
    public $pageFactory;

    /** @var $productName */
    public $productName;

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

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $this->coreRegistry->register('current_product','');
        $this->coreRegistry->register('product_name',$this->getRequest()->getParam('productName'));
        return $resultPage;
    }
}
