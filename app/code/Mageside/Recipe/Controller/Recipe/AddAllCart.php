<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Controller\Recipe;

use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;

class AddAllCart extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Mageside\Recipe\Model\ResourceModel\RecipeProduct\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FormKeyValidator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * AddAllCart constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param \Mageside\Recipe\Model\ResourceModel\RecipeProduct\CollectionFactory $collectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param FormKeyValidator $formKeyValidator
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        \Mageside\Recipe\Model\ResourceModel\RecipeProduct\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        FormKeyValidator $formKeyValidator,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $collectionFactory;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->layoutFactory = $layoutFactory;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $result = ['success' => false];
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultJson->setData($result);
        }

        if ($recipeId = $this->getRequest()->getParam('recipe_id')) {
            try {
                if ($ingredients = $this->getRequest()->getParam('ingredients')) {
                    $products = $this->productCollectionFactory->create()
                        ->addRecipeFilter($recipeId)
                        ->getProductCollection($this->storeManager->getStore()->getId())
                        ->getItems();

                    $count = 0;
                    foreach ($ingredients as $productId => $params) {
                        if (isset($params['qty']) && $params['qty'] == 0) {
                            continue;
                        }
                        if (isset($products[$productId])) {
                            /** @var \Magento\Catalog\Model\Product $product */
                            $product = $products[$productId];
                            if ($product->getIsSalable()) {
                                $this->cart->addProduct($product, $params);
                                $count++;
                            }
                        }
                    }

                    if ($count > 0) {
                        $this->cart->save();
                        $result = ['success' => true];
                        $this->messageManager->addSuccessMessage(__('Products successfully added to the cart.'));
                    } else {
                        $result = ['success' => true];
                        $this->messageManager->addErrorMessage(__('No products have been added to the cart.'));
                    }
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage(__('Something went wrong during adding products to the cart.'));
            }
        }

        if (!$result['success']) {
            /** @var $block \Magento\Framework\View\Element\Messages */
            $block = $this->layoutFactory->create()->getMessagesBlock();
            $block->setMessages($this->messageManager->getMessages(true));
            $result['messages'] = $block->getGroupedHtml();
        }

        return $resultJson->setData($result);
    }
}
