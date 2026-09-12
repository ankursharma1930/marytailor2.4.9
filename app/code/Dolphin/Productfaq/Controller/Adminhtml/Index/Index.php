<?php

namespace Dolphin\Productfaq\Controller\Adminhtml\Index;

use Magento\Framework\Controller\Result\RawFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use Dolphin\Productfaq\Model\ProductfaqFactory;
use Magento\Framework\Registry;
use Magento\Cms\Model\Wysiwyg\Config;
use Dolphin\Productfaq\Block\Adminhtml\Productfaq\Tab\Product;
use Magento\Framework\Controller\ResultInterfacel;
use Dolphin\Productfaq\Model\Productfaq;

class Index extends Action
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Config
     */
    protected $wysiwygConfig;

    /**
     * @var ProductfaqFactory
     */
    protected $productFaq;

    /**
     * Constructor
     *
     * @param Context           $context
     * @param RawFactory        $resultRawFactory
     * @param LayoutFactory     $layoutFactory
     * @param Registry          $registry
     * @param Config            $wysiwygConfig
     * @param ProductfaqFactory $productFaq
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory,
        Registry $registry,
        Config $wysiwygConfig,
        ProductfaqFactory $productFaq
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
        $this->registry = $registry;
        $this->wysiwygConfig = $wysiwygConfig;
        $this->productFaq = $productFaq;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $item = $this->_initItem(true);
        if (!$item) {

            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('productfaq/productfaq/new', ['_current' => true, 'id' => null]);
        }

        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents(
            $this->layoutFactory->create()->createBlock(
                Product::class,
                'productfaq.product.grid'
            )->toHtml()
        );
    }

    /**
     * Initialize the item.
     *
     * @param bool $getRootInstead
     * @return Productfaq
     */
    protected function _initItem($getRootInstead = false)
    {
        $id = (int) $this->getRequest()->getParam('id', false);
        $myModel = $this->productFaq->create();
        if ($id) {
            $myModel->load($id);
        }
        $this->registry->register('product', $myModel);
        $this->registry->register('productfaq', $myModel);
        $this->wysiwygConfig;

        return $myModel;
    }
}
