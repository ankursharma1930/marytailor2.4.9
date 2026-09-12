<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Ui\DataProvider\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Mageside\Recipe\Model\FileUploader;
use Mageside\Recipe\Model\Recipe\FilterFactory;
use Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Collection;

class RecipeFilterDataProvider extends AbstractDataProvider
{
    /**
     * @var \Mageside\Recipe\Model\FileUploader
     */
    protected $_fileUploader;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var \Mageside\Recipe\Model\Recipe\Filter
     */
    protected $filterFactory;

    /**
     * RecipeFilterDataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Collection $collection
     * @param \Mageside\Recipe\Model\Recipe\FilterFactory $filterFactory
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Collection $collection,
        FilterFactory $filterFactory,
        FileUploader $fileUploader,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collection;
        $this->_fileUploader = $fileUploader;
        $this->_request = $request;
        $this->filterFactory = $filterFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getMeta()
    {
        $meta = parent::getMeta();

        $joinFields = [
            'type' => ['input', "field"]
        ];
        if ($filterId = $this->_request->getParam('id')) {
            $filterItem = $this->filterFactory->load($filterId);
            $data = $this->prepareData($filterItem);

            foreach ($joinFields as $fieldName => $fieldData) {
                if (isset($data[$fieldName . '_is_default']) && $this->_request->getParam('store')) {
                    $useDefaultConfig = [
                        'formElement' => $fieldData[0],
                        'componentType' => $fieldData[1],
                        'usedDefault' => $data[$fieldName . '_default_value'] ? true : false,
                        'disabled' => $data[$fieldName . '_is_default'] ? true : false,
                        'service' => [
                            'template' => 'ui/form/element/helper/service',
                        ]
                    ];
                    $meta['recipe_filter_form']['children'][$fieldName]['arguments']
                    ['data']['config'] = $useDefaultConfig;
                }
            }

            if ($this->_request->getParam('store')) {
                $useDefaultConfigs = [
                    'formElement' => "input",
                    'component' => 'Mageside_Recipe/js/extend/filter-options-data-row',
                    'componentType' => "field",
                    'service' => [
                        'template' => 'Mageside_Recipe/form/element/helper/service',
                    ]
                ];
                $meta['recipe_filter_form']['children']['options']['children']['label']['arguments']
                ['data']['config'] = $useDefaultConfigs;
            }
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if ($filterId = $this->_request->getParam('id')) {
            $filterItem = $this->filterFactory->load($filterId);

            if ($filterItem->getId()) {
                $this->data[$filterItem->getId()]['filter'] = $this->prepareData($filterItem);
            }
        }

        return $this->data;
    }

    /**
     * @param $filterItem
     * @return mixed
     */
    protected function prepareData($filterItem)
    {
        $options = $filterItem->getOptions();
        $prepareOption = $filterItem->toArray();
        foreach ($options as $key => $option) {
            if (!empty($option['option_image_default_value'])) {
                $imageData = [
                    'name' => $option['option_image'],
                    'url' => $this->_fileUploader->getFileWebUrl($option['option_image_default_value'])
                ];
                $prepareOption['options'][$key]['option_image_default_value'] = [];
                $prepareOption['options'][$key]['option_image_default_value'][] = $imageData;
                $prepareOption['options'][$key]['option_image_is_default'] = 1;
            }
            if (!empty($option['option_image'])) {
                $imageData = [
                    'name' => $option['option_image'],
                    'url' => $this->_fileUploader->getFileWebUrl($option['option_image'])
                ];
                $prepareOption['options'][$key]['option_image'] = [];
                $prepareOption['options'][$key]['option_image'][] = $imageData;
                $prepareOption['options'][$key]['option_image_is_default'] = 0;
            }
        }

        return $prepareOption;
    }
}
