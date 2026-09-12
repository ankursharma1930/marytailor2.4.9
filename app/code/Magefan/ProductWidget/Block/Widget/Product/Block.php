<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\ProductWidget\Block\Widget\Product;

/**
 * Widget Product Block
 */
class Block extends \Magento\Catalog\Block\Product\AbstractProduct implements \Magento\Widget\Block\BlockInterface
{
    /**
     * Default template file
     */
    const DEFAULT_TEMPLATE = 'Magefan_ProductWidget::default.phtml';

    /**
     * Default product description attribute code
     */
    const DEFAULT_PRODUCT_DESCRIPTION_ATTRIBUTE_CODE = 'short_description';

    /**
     * Default product image width in pixels
     */
    const DEFAULT_PRODUCT_IMAGE_WIDTH = 240;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Helper\Output
     */
    protected $outputhelper;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * Block constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Helper\Output $outputhelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Output $outputhelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productRepository = $productRepository;
        $this->imageHelper = $context->getImageHelper();
        $this->outputhelper = $outputhelper;
        $this->urlHelper = $urlHelper;
    }

    /**
     * Set blog template
     *
     * @return this
     */
    public function _toHtml()
    {
        $template = $this->getCustomTemplate() ?: self::DEFAULT_TEMPLATE;
        if ($this->isAmpRequest()) {
            $template = str_replace('.phtml', '-amp.phtml', $template);
        }
        $this->setTemplate($template);
        return parent::_toHtml();
    }

    /**
     *
     */
    protected function isAmpRequest()
    {
        /* We know that using objectManager is not a not a good practice,
            but if Plumrocket_AMP is not installed on your magento instance
            you'll get error during di:compile */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $isAmpRequest = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue('pramp/general/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($isAmpRequest) {
            $isAmpRequest = $objectManager->get('\Plumrocket\Amp\Helper\Data')
                ->isAmpRequest();
        }

        return $isAmpRequest;
    }

    /**
     * Retrieve product object
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        try {
            $productId = explode('/', $this->getProductId());
            if (isset($productId[1])) {
                $productId = $productId[1];
            } else {
                $productId = 0;
            }
            return $this->productRepository->getById($productId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Retrieve true if can display product image block
     *
     * @return bool
     */
    public function displayImage()
    {
        return (bool)$this->getDisplayImage();
    }

    /**
     * @return string
     */
    public function getProductImage()
    {
        return $this->imageHelper->init($this->getProduct(), 'category_page_list')
            ->constrainOnly(false)
            ->keepAspectRatio(true)
            ->keepFrame(false)
            ->resize($this->getImageWidth(), null)
            ->getUrl();
    }

    /**
     * Retrieve product width in pixels
     *
     * @return bool
     */
    public function getImageWidth()
    {
        $width = $this->getData('image_width') ?: self::DEFAULT_PRODUCT_IMAGE_WIDTH;
        return (int) $width;
    }

    /**
     * Retrieve true if can display product reviews block
     *
     * @return bool
     */
    public function displayReviews()
    {
        return (bool)$this->getDisplayReviews();
    }

    /**
     * Retrieve true if can display product price block
     *
     * @return bool
     */
    public function displayPrice()
    {
        return (bool)$this->getDisplayPrice();
    }

    /**
     * Retrieve true if can display product add to cart button
     *
     * @return bool
     */
    public function displayAddToCart()
    {
        return (bool)$this->getDisplayAddToCart();
    }

    /**
     * Get post parameters
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getAddToCartPostParams(\Magento\Catalog\Model\Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl($url),
            ]
        ];
    }

    /**
     * Retrieve true if can display product description block
     *
     * @return bool
     */
    public function displayDescription()
    {
        return (bool)$this->getDisplayDescription();
    }

    /**
     * Retrieve product description
     *
     * @return string
     */
    public function getProductDescription()
    {
        $descriptionType = $this->getDisplayDescription();

        if (!$descriptionType) {
            return;
        }

        if ($descriptionType == 3) {
            return $this->escapeHtml(
                $this->getDescriptionText()
            );
        }

        if ($descriptionType == 1) {
            $productAttr = self::DEFAULT_PRODUCT_DESCRIPTION_ATTRIBUTE_CODE;
        } else {
            $productAttr = $this->getDescriptionAttribute();
        }



        $product = $this->getProduct();
        return $this->outputhelper->productAttribute(
            $product,
            $product->getData($productAttr),
            $productAttr
        );
    }

    /**
     * Retrieve product name
     *
     * @return string
     */
    public function getProductName()
    {
        $product = $this->getProduct();
        return $this->outputhelper->productAttribute($product, $product->getName(), 'name');
    }

    /**
     * Retrieve true if can display product description block
     *
     * @return bool
     */
    public function displayReadMore()
    {
        return (bool)$this->getDisplayReadMore();
    }

    /**
     * Retrieve read more text
     *
     * @return string
     */
    public function getReadMoreText()
    {
        return $this->getData('read_more_text') ?: __('Click here for more details');
    }

    /**
     * Retrieve review template type
     *
     * @return string
     */
    public function getReviewTemplateType()
    {
        return \Magento\Catalog\Block\Product\ReviewRendererInterface::SHORT_VIEW;
    }
}
