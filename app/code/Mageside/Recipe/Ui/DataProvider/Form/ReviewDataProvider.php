<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Ui\DataProvider\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Registry;

class ReviewDataProvider extends AbstractDataProvider
{
    /**
     * Core registry
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * ReviewDataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\Framework\Registry $registry
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Registry $registry,
        array $meta = [],
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        return $meta;
    }

    /**
     * Get data
     * @return mixed
     */
    public function getData()
    {
        $reviewData = null;
        if ($this->_coreRegistry->registry('review_data')) {
            $reviewData = $this->_coreRegistry->registry('review_data');
            $this->data['config']['data'] = $reviewData->toArray();
        } elseif ($this->_coreRegistry->registry('new_review_data')) {
            $reviewData = $this->_coreRegistry->registry('new_review_data');
        }
        if (is_object($reviewData)) {
            $this->data['config']['data']['recipe_id'] = $reviewData->getEntityPkValue();
            $this->data['config']['data']['select_stores'] = $reviewData->getStores();
        }

        return $this->data;
    }

    /**
     * Add field filter to collection
     * @param \Magento\Framework\Api\Filter $filter
     * @return mixed
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
    }
}
