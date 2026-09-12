<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Ui\Component\Listing\Column;

use Mageside\Recipe\Helper\Image;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Asset\Repository;

/**
 * Class Thumbnail
 * @package Mageside\Recipe\Ui\Component\Listing\Column
 */
class Thumbnail extends Column
{
    const ALT_FIELD = 'thumbnail';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * Thumbnail constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param Image $imageHelper
     * @param Repository $assetRepo
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        Image $imageHelper,
        Repository $assetRepo,
        array $components = [],
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->_urlBuilder = $urlBuilder;
        $this->_assetRepo = $assetRepo;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $url = '';
                if ($item[$fieldName] != '') {
                    $url = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                        . 'recipe/' . $item[$fieldName];
                }
                $resizedImage = $this->imageHelper->getResizedImage($url, 'recipe_grid_image_small')
                    ?: $this->_assetRepo->getUrl('Magento_Catalog::images/product/placeholder/thumbnail.jpg');
                $item[$fieldName . '_src'] = $resizedImage;
                $item[$fieldName . '_alt'] = $this->getAlt($item) ?: '';
                $item[$fieldName . '_link'] = $resizedImage;
                $item[$fieldName . '_orig_src'] = $url;
            }
        }

        return $dataSource;
    }

    /**
     * @param array $row
     * @return null|string
     */
    protected function getAlt($row)
    {
        $altField = $this->getData('config/altField') ?: self::ALT_FIELD;
        return isset($row[$altField]) ? $row[$altField] : null;
    }
}
