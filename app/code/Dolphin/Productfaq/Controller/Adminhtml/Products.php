<?php

namespace Dolphin\Productfaq\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Dolphin\Productfaq\Model\Products as ModelProducts;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Catalog\Model\Category;

abstract class Products extends Action
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ModelProducts
     */
    protected $productModel;

    /**
     * @var WysiwygConfig
     */
    protected $wysiwygConfig;

    /**
     * @param Action\Context $context
     * @param Registry $registry
     * @param ModelProducts $productModel
     * @param WysiwygConfig $wysiwygConfig
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        ModelProducts $productModel,
        WysiwygConfig $wysiwygConfig
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->productModel = $productModel;
        $this->wysiwygConfig = $wysiwygConfig;
    }

    /**
     * Initialize requested category and put it into registry.
     *
     * @param bool $getRootInstead
     * @return Category|false
     */
    protected function _initItem()
    {
        $id = (int) $this->getRequest()->getParam('productfaq_id', false);
        $product = $this->productModel;
        if ($id) {
            $product->load($id);
        }
        $this->registry->register('product', $product);
        $this->registry->register('productfaq', $product);
        $this->wysiwygConfig;

        return $product;
    }
}
