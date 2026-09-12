<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Component\Product;

/**
 * Class Bestsellers
 */
class View extends \Plumrocket\AmpEmailApi\Block\AbstractProductComponent
{
    /**
     * @var string
     */
    protected $styleFileId = 'Plumrocket_AmpEmail::css/component/v1/product/view/default.css';

    /**
     * @var \Plumrocket\AmpEmail\Api\CanOneClickAddToCartInterface
     */
    private $canOneClickAddToCart;

    /**
     * TODO: replace with ImageFactory after left support magento v2.2
     *
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    private $imageBuilder;

    /**
     * @var \Magento\Catalog\Block\Product\View\GalleryFactory
     */
    private $galleryFactory;

    /**
     * @var \Magento\Catalog\Block\Product\View\Gallery|null
     */
    private $gallery;

    /**
     * @var \Plumrocket\AmpEmail\Model\Image\Compressor
     */
    private $imageCompressor;

    /**
     * View constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context           $context
     * @param \Magento\Framework\Url                                     $frontUrlBuilder
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface  $componentDataLocator
     * @param \Magento\Framework\View\Asset\Repository                   $viewAssetRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface            $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface          $priceCurrency
     * @param \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface $initFrontProductPrice
     * @param \Plumrocket\AmpEmail\Api\CanOneClickAddToCartInterface     $canOneClickAddToCart
     * @param \Magento\Catalog\Block\Product\ImageBuilder                $imageBuilder
     * @param \Magento\Catalog\Block\Product\View\GalleryFactory         $galleryFactory
     * @param \Plumrocket\AmpEmail\Model\Image\Compressor                $imageCompressor
     * @param array                                                      $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url $frontUrlBuilder,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Magento\Framework\View\Asset\Repository $viewAssetRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Plumrocket\AmpEmailApi\Api\InitFrontProductPriceInterface $initFrontProductPrice,
        \Plumrocket\AmpEmail\Api\CanOneClickAddToCartInterface $canOneClickAddToCart,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Catalog\Block\Product\View\GalleryFactory $galleryFactory,
        \Plumrocket\AmpEmail\Model\Image\Compressor $imageCompressor,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $frontUrlBuilder,
            $componentDataLocator,
            $viewAssetRepository,
            $productRepository,
            $priceCurrency,
            $initFrontProductPrice,
            $data
        );
        $this->canOneClickAddToCart = $canOneClickAddToCart;
        $this->imageBuilder = $imageBuilder;
        $this->galleryFactory = $galleryFactory;
        $this->imageCompressor = $imageCompressor;
    }

    /**
     * @param int $productId
     * @return string
     */
    public function getActualInfoUrl(int $productId) : string
    {
        return $this->getAmpApiUrl('amp-email-api/V1/product_actual_info', ['product' => $productId]);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (! $this->getProduct()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return bool
     */
    public function isProductHasRequireOption() : bool
    {
        return ! $this->canOneClickAddToCart->execute($this->getProduct());
    }

    /**
     * @return bool
     */
    public function isGuest() : bool
    {
        return ! $this->getComponentDataLocator()->getCustomerId();
    }

    /**
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getProductImage() : \Magento\Catalog\Block\Product\Image
    {
        return $this->imageBuilder->create($this->getProduct(), 'pr_amp_email_product_view');
    }

    /**
     * @return string
     */
    public function getProductImageAlt() : string
    {
        return $this->getProduct()->getName();
    }

    /**
     * @return \Magento\Framework\Data\Collection
     */
    public function getGalleryImages()
    {
        if (null === $this->gallery) {
            $this->gallery = $this->galleryFactory->create();
            $this->gallery->setProduct($this->getProduct());
        }

        return $this->gallery->getGalleryImages();
    }

    /**
     * @param      $imageId
     * @param      $attributeName
     * @param null $default
     * @return string|int
     */
    public function getImageAttribute($imageId, $attributeName, $default = null)
    {
        if (null === $this->gallery) {
            $this->gallery = $this->galleryFactory->create();
            $this->gallery->setProduct($this->getProduct());
        }

        return $this->gallery->getImageAttribute($imageId, $attributeName, $default);
    }

    /**
     * @param string     $imagePath
     * @param int        $width
     * @param null|int   $height
     * @return array|bool
     */
    public function getProductGalleryPreviewImage(string $imagePath, $width = 120, $height = null)
    {
        return $this->imageCompressor->getProductGalleryPreviewImage($imagePath, $width, $height);
    }
}
