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

/**
 * Class Actions
 */
class Actions extends Column
{
    /**
     * @var UrlInterface
     */
    private $_urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $storeId = $this->context->getFilterParam('store_id');

            $config = $this->getData();
            $indexField = isset($config['config']['indexField']) ? $config['config']['indexField'] : 'filter_id';

            if (isset($this->getData('config')['actions'])) {
                $actions = $this->getData('config')['actions'];
                foreach ($dataSource['data']['items'] as &$item) {
                    reset($actions);
                    $columnName = $this->getData('name');
                    foreach ($actions as $name => $action) {
                        $requestField = isset($action['requestField']) ? $action['requestField'] : $indexField;
                        $item[$columnName][$name] = [
                            'href' => $this->_urlBuilder->getUrl(
                                $action['href'],
                                [$requestField => $item[$indexField], 'store' => $storeId]
                            ),
                            'label' => $action['label'],
                            'hidden' => false,
                        ];
                        if (isset($action['confirm'])) {
                            $item[$columnName][$name]['confirm'] = $action['confirm'];
                        }
                    }
                }
            }
        }

        return $dataSource;
    }
}
