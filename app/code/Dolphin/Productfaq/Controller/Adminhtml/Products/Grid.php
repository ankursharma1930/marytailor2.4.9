<?php
namespace Dolphin\Productfaq\Controller\Adminhtml\Products;

use Dolphin\Productfaq\Controller\Adminhtml\Products as productFaqProducts;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Dolphin\Productfaq\Block\Adminhtml\Productfaq\Tab\Product;
use Magento\Framework\Registry;
use Dolphin\Productfaq\Model\Products as ModelProducts;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;

class Grid extends productFaqProducts
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
     * @param Context       $context
     * @param Registry      $registry
     * @param ModelProducts $modelProducts
     * @param WysiwygConfig $wysiwygConfig
     * @param Rawfactory    $resultRawFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ModelProducts $modelProducts,
        WysiwygConfig $wysiwygConfig,
        Rawfactory $resultRawFactory,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($context, $registry, $modelProducts, $wysiwygConfig);
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Execute action
     *
     * @return RawFactory
     */
    public function execute()
    {
        $item = $this->_initItem(true);
        if (!$item) {
            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath('*/*/new', ['_current' => true, 'id' => null]);
        }

        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents(
            $this->layoutFactory->create()->createBlock(
                Product::class,
                'productfaq.product.grid'
            )->toHtml()
        );
    }
}
