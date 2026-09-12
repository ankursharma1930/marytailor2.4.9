<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\ItemProvider;

use Magento\Framework\DataObject;
use Magento\Sitemap\Model\ItemProvider\ConfigReaderInterface;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;
use Mageside\Recipe\Helper\Config;
use Mageside\Recipe\Helper\Image;
use Mageside\Recipe\Model\ResourceModel\Recipe\CollectionFactory;

class RecipeSiteMap implements ItemProviderInterface
{
    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * Recipe CollectionFactory
     * @var CollectionFactory
     */
    private $recipeCollectionFactory;

    /**
     * Sitemap item factory
     *
     * @var SitemapItemInterfaceFactory
     */
    private $itemFactory;

    /**
     * Config reader
     *
     * @var ConfigReaderInterface
     */
    private $configReader;

    /**
     * @var Config
     */
    private $recipeConfig;

    /**
     * RecipeSiteMap constructor.
     * @param ConfigReaderInterface $configReader
     * @param CollectionFactory $recipeCollectionFactory
     * @param SitemapItemInterfaceFactory $itemFactory
     * @param Image $imageHelper
     * @param Config $recipeConfig
     */
    public function __construct(
        ConfigReaderInterface $configReader,
        CollectionFactory $recipeCollectionFactory,
        SitemapItemInterfaceFactory $itemFactory,
        Image $imageHelper,
        Config $recipeConfig
    ) {
        $this->recipeCollectionFactory = $recipeCollectionFactory;
        $this->itemFactory = $itemFactory;
        $this->configReader = $configReader;
        $this->imageHelper = $imageHelper;
        $this->recipeConfig = $recipeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($storeId)
    {
        if ($this->recipeConfig->isEnabled()) {
            $recipeCollection = $this->recipeCollectionFactory->create()
                ->joinRecipeData()
                ->addIsEnableFilter();
            $items = array_map(function ($item) use ($storeId) {
                return $this->itemFactory->create([
                    'url' => $this->recipeConfig->getSeoRoute() . '/' . $item->getUrlKey() . $this->recipeConfig->getSeoPostfix(),
                    'updatedAt' => date("Y-m-d h:i:s"),
                    'images' => new DataObject([
                        'collection' => [
                            new DataObject([
                                'url' => (
                                    $item->getMediaTypeImage()
                                    ? $this->imageHelper->getMediaLink($item->getMediaTypeImage())
                                    : ''
                                ),
                                'caption' => ''
                            ]),
                        ],
                        'title' => $item->getTitle(),
                        'thumbnail' => (
                            $item->getThumbnail()
                            ? $this->imageHelper->getMediaLink($item->getThumbnail())
                            : ''
                        ),
                    ]),
                    'priority' => $this->configReader->getPriority($storeId),
                    'changeFrequency' => $this->configReader->getChangeFrequency($storeId),
                ]);
            }, $recipeCollection->getItems());

            return $items;
        } else {

            return [];
        }
    }
}
