<?php

namespace Dolphin\Productfaq\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form;

class Productfaq extends AbstractModifier
{
    protected const GROUP_SMART_DOCUMENT = 'productfaq';
    protected const GROUP_CONTENT = 'content';
    protected const SORT_ORDER = 100;
    protected const LINK_TYPE = 'associated';

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param LocatorInterface $locator
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder
    ) {
        $this->locator = $locator;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $meta[static::GROUP_SMART_DOCUMENT] = [
            'children' => [
                'dolphin_userguide_listing' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => 'insertListing',
                                'dataScope' => 'product_question_listing',
                                'externalProvider' => 'product_question_listing.product_question_listing_data_source',
                                'selectionsProvider' =>
                                            'product_question_listing.product_question_listing.product_columns.ids',
                                'ns' => 'product_question_listing',
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => false,
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'productId' => '${ $.provider }:data.product.current_product_id',
                                    '__disableTmpl' => ['productId' => false],
                                ],
                                'exports' => [
                                    'productId' => '${ $.externalProvider }:params.current_product_id',
                                    '__disableTmpl' => ['productId' => false],
                                ],

                            ],
                        ],
                    ],
                ],
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Product Question'),
                        'collapsible' => true,
                        'opened' => false,
                        'componentType' => Form\Fieldset::NAME,
                        'sortOrder' =>
                        $this->getNextGroupSortOrder(
                            $meta,
                            static::GROUP_CONTENT,
                            static::SORT_ORDER
                        ),
                    ],
                ],
            ],
        ];
        return $meta;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function modifyData(array $data)
    {
        $productId = $this->locator->getProduct()->getId();
        $storeId = $this->locator->getStore()->getId();
        $data[$productId][self::DATA_SOURCE_DEFAULT]['current_product_id'] = $productId;
        $data[$storeId][self::DATA_SOURCE_DEFAULT]['current_store_id'] = $storeId;
        return $data;
    }
}
