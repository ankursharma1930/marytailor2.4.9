<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Ui\DataProvider\Form\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class AssignedProductsData extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Related
{
    private static $previousGroup = 'cooking';

    private static $sortOrder = 90;

    /**
     * @var \Magento\Catalog\Ui\Component\Listing\Columns\Price
     */
    private $_priceModifier;

    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory
     */
    protected $_productLinkFactory;

    /**
     * AssignedProductsData constructor.
     * @param LocatorInterface $locator
     * @param UrlInterface $urlBuilder
     * @param ProductLinkRepositoryInterface $productLinkRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ImageHelper $imageHelper
     * @param Status $status
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param \Magento\Catalog\Ui\Component\Listing\Columns\Price $priceModifier
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder,
        ProductLinkRepositoryInterface $productLinkRepository,
        ProductRepositoryInterface $productRepository,
        ImageHelper $imageHelper,
        Status $status,
        AttributeSetRepositoryInterface $attributeSetRepository,
        \Magento\Catalog\Ui\Component\Listing\Columns\Price $priceModifier,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory
    ) {
        $this->_priceModifier = $priceModifier;
        $this->_coreRegistry = $coreRegistry;
        $this->_productLinkFactory = $productLinkFactory;

        parent::__construct(
            $locator,
            $urlBuilder,
            $productLinkRepository,
            $productRepository,
            $imageHelper,
            $status,
            $attributeSetRepository,
            'recipe_form.recipe_form',
            ''
        );
    }

    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive(
            $meta,
            [
                static::GROUP_RELATED => [
                    'children' => [
                        $this->scopePrefix . static::DATA_SCOPE_RELATED => $this->getRelatedFieldset()
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Assigned Products'),
                                'collapsible' => true,
                                'componentType' => Fieldset::NAME,
                                'dataScope' => static::DATA_SCOPE,
                                'sortOrder' =>
                                    $this->getNextGroupSortOrder(
                                        $meta,
                                        self::$previousGroup,
                                        self::$sortOrder
                                    ),
                            ],
                        ],

                    ],
                ],
            ]
        );

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        /** @var \Mageside\Recipe\Model\Recipe $recipe */
        $recipe = $this->_coreRegistry->registry('current_recipe');
        if (!$recipe) {
            return $data;
        }
        $recipeId = $recipe->getRecipeId();

        $priceModifier = $this->_priceModifier;
        $priceModifier->setData('name', 'price');

        $products = $recipe->getAssignedProducts(0)->getItems();
        if (!empty($products)) {
            $dataScope = $this->scopeName;
            $data[$recipeId][$dataScope] = [];
            $count = 0;
            foreach ($products as $product) {
                /** @var \Magento\Catalog\Model\Product $product */
                $productLink = $this->_productLinkFactory->create();
                $productLink
                    ->setLinkedProductSku($product->getSku())
                    ->setPosition($count++);
                $data[$recipeId]['related'][] = $this->getProductData(
                    $product,
                    $productLink,
                    $product->getQty()
                );
            }
            if (!empty($data[$recipeId]['related'])) {
                $dataMap = $priceModifier->prepareDataSource([
                    'data' => [
                        'items' => $data[$recipeId]['related']
                    ]
                ]);
                $data[$recipeId]['related'] = $dataMap['data']['items'];
            }
        }

        return $data;
    }

    /**
     * @param $assignedProduct
     * @param $productLink
     * @param $qty
     * @return array
     */
    public function getProductData($assignedProduct, $productLink, $qty)
    {
        $fillData = $this->fillData($assignedProduct, $productLink);
        $fillData['qty'] = floatval($qty);

        return $fillData;
    }

    /**
     * @param string $scope
     * @return array
     */
    protected function getGrid($scope)
    {
        $dataProvider = $scope . '_product_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__field-wide',
                        'componentType' => DynamicRows::NAME,
                        'label' => null,
                        'columnsHeader' => false,
                        'columnsHeaderAfterRender' => true,
                        'renderDefaultRecord' => false,
                        'template' => 'ui/dynamic-rows/templates/grid',
                        'component' => 'Mageside_Recipe/js/components/dynamic-rows-grid',
                        'addButton' => false,
                        'recordTemplate' => 'record',
                        'dataScope' => 'data',
                        'deleteButtonLabel' => __('Remove'),
                        'dataProvider' => $dataProvider,
                        'map' => [
                            'id' => 'entity_id',
                            'name' => 'name',
                            'status' => 'status_text',
                            'attribute_set' => 'attribute_set_text',
                            'sku' => 'sku',
                            'price' => 'price',
                            'thumbnail' => 'thumbnail_src',
                        ],
                        'links' => [
                            'insertData' => '${ $.provider }:${ $.dataProvider }',
                            '__disableTmpl' => ['insertData' => false],
                        ],
                        'sortOrder' => 2,
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'isTemplate' => true,
                                'is_collection' => true,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'dataScope' => '',
                            ],
                        ],
                    ],
                    'children' => $this->getColumns(),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        $columns = $this->fillMeta();
        $columns['thumbnail']['arguments']['data']['config']['template'] = 'ui/form/field';
        $columns['qty'] =  [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataType' => Number::NAME,
                        'formElement' => Input::NAME,
                        'template' => 'ui/form/field',
                        'componentType' => Field::NAME,
                        'dataScope' => 'qty',
                        'label' => __('Quantity'),
                        'sortOrder' => 65,
                        'fit' => true,
                        'visible' => true,
                        "validation" => [
                            "required-entry" => true,
                            "validate-number" => true,
                        ],
                    ],
                ],
            ]
        ];

        uasort($columns, function ($item1, $item2) {
            $sort1 = $item1['arguments']['data']['config']['sortOrder'];
            $sort2 = $item2['arguments']['data']['config']['sortOrder'];
            if ($sort1 == $sort2) {
                return 0;
            }
            return $sort1 > $sort2 ? 1 : -1;
        });

        return $columns;
    }

    /**
     * @inheritdoc
     */
    protected function getTextColumn($dataScope, $fit, Phrase $label, $sortOrder)
    {
        $column = parent::getTextColumn($dataScope, $fit, $label, $sortOrder);
        $column['arguments']['data']['config']['template'] = 'ui/form/field';

        return $column;
    }

    /**
     * @param Phrase $title
     * @param string $scope
     * @return array
     */
    protected function getGenericModal(Phrase $title, $scope)
    {
        $listingTarget = $scope . '_product_listing';

        $modal = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'dataScope' => '',
                        'options' => [
                            'title' => $title,
                            'buttons' => [
                                [
                                    'text' => __('Cancel'),
                                    'actions' => [
                                        'closeModal'
                                    ]
                                ],
                                [
                                    'text' => __('Add Assigned Products'),
                                    'class' => 'action-primary',
                                    'actions' => [
                                        [
                                            'targetName' => 'index = ' . $listingTarget,
                                            'actionName' => 'save'
                                        ],
                                        'closeModal'
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'children' => [
                $listingTarget => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => false,
                                'componentType' => 'insertListing',
                                'cssclass' => 'test',
                                'dataScope' => $listingTarget,
                                'externalProvider' => $listingTarget . '.' . $listingTarget . '_data_source',
                                'selectionsProvider' => $listingTarget . '.' . $listingTarget . '.product_columns.ids',
                                'ns' => $listingTarget,
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => true,
                                'dataLinks' => [
                                    'imports' => false,
                                    'exports' => true
                                ],
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'filterId' => '${ $.provider }:data.filter.current_filter_id',
                                    'storeId' => '${ $.provider }:data.filter.current_store_id',
                                ],
                                'exports' => [
                                    'filterId' => '${ $.externalProvider }:params.current_filter_id',
                                    'storeId' => '${ $.externalProvider }:params.current_store_id',
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $modal;
    }

    /**
     * @return array
     */
    protected function getRelatedFieldset()
    {
        $content = __(
            'Assigned products are shown to customers in addition to the item the customer is looking at.'
        );

        return [
            'children' => [
                'button_set' => $this->getButtonSet(
                    $content,
                    __('Add Assigned Products'),
                    $this->scopePrefix . static::DATA_SCOPE_RELATED
                ),
                'modal' => $this->getGenericModal(
                    __('Add Assigned Products'),
                    $this->scopePrefix . static::DATA_SCOPE_RELATED
                ),
                static::DATA_SCOPE_RELATED => $this->getGrid($this->scopePrefix . static::DATA_SCOPE_RELATED),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__fieldset-section',
                        'label' => __('Assigned Products'),
                        'collapsible' => false,
                        'componentType' => Fieldset::NAME,
                        'dataScope' => '',
                        'sortOrder' => 10,
                    ],
                ],
            ]
        ];
    }
}
