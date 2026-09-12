<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class WriterActions
 * @package Mageside\Recipe\Ui\Component\Listing\Column
 */
class WriterActions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * WriterActions constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        RequestInterface $request,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
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
            $storeId = $this->context->getFilterParam('store_id');
            $recipeId = $this->request->getParam('recipe');
            if (!empty($recipeId)) {
                foreach ($dataSource['data']['items'] as &$item) {
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'recipe/recipe/edit',
                            [
                                "recipe_id" => $recipeId['recipe_id'],
                                'customer_id' => $item['entity_id'],
                                'store' => $storeId
                            ]
                        ),
                        'label' => __('Choose'),
                        'hidden' => false,
                    ];
                }
            } else {
                foreach ($dataSource['data']['items'] as &$item) {
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'recipe/recipe/new',
                            [
                                'customer_id' => $item['entity_id'],
                                'store' => $storeId
                            ]
                        ),
                        'label' => __('Choose'),
                        'hidden' => false,
                    ];
                }
            }
        }

        return $dataSource;
    }
}
