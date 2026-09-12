<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\ResourceModel\Recipe\Filter;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Options extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * Options constructor.
     * @param Context $context
     * @param RequestInterface $request
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        $connectionName = null
    ) {
        $this->_request = $request;
        parent::__construct($context, $connectionName);
    }

    protected function _construct()
    {
        $this->_init('ms_recipe_filter_options', 'id');
    }

    /**
     * Save options
     * @param $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveOptions($data)
    {
        $this->getConnection()->insertMultiple($this->getMainTable(), $data);

        return $this;
    }

    /**
     * Update options
     * @param $options
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateOptions($options)
    {
        $connection = $this->getConnection();

        foreach ($options as $option) {
            $connection->update(
                $this->getMainTable(),
                [
                    'slug' => !empty($option['slug']) ? $option['slug'] : null
                ],
                ['id = ?' => (int)$option['id']]
            );
        }

        return $this;
    }

    /**
     * Delete options by ids
     * @param $ids
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteOptions($ids)
    {
        $connection = $this->getConnection();
        $connection->delete($this->getMainTable(), ['id IN (?)' => $ids]);

        return $this;
    }

    /**
     * @param $tableName
     * @param $prepareDate
     */
    protected function insertData($tableName, $prepareDate)
    {
        $select = $this->getConnection()->insert(
            $tableName,
            $prepareDate
        );
    }

    /**
     * @param $tableName
     * @param $prepareDate
     * @param $optionId
     * @param $fieldName
     * @param $currentStoreId
     * @param $filterOptionId
     */
    protected function updateData($tableName, $prepareDate, $optionId, $fieldName, $currentStoreId, $filterOptionId)
    {
        $select = $this->getConnection()->update(
            $tableName,
            $prepareDate,
            "id = " . $optionId . " AND meta_key = '" . $fieldName . "' AND store_id = "
            . $currentStoreId . " AND filter_option_id = " . $filterOptionId
        );
    }

    /**
     * @param $object
     */
    public function saveFilterOptionsData($object)
    {
        $currentOptions = $object->loadOptionsByFilterId($object->getId())->getData();
        $currentStoreId = $object->getData('store_id');
        $tableName = $this->getTable('ms_recipe_filter_options_varchar');
        $optionsDeleteIds = [];
        if (isset($this->_request->getParam('filter')['options'])) {
            $joinFields = [
                'label', 'option_image'
            ];
            $options = $this->_request->getParam('filter')['options'];
            foreach ($options as $key => $option) {
                foreach ($joinFields as $fieldName) {
                    $optionId = intval($this->getConnection()->fetchOne(
                        $this->getConnection()->select()
                            ->from($tableName, ['id'])
                            ->where(
                                "meta_key = '" . $fieldName . "' AND store_id = " . $currentStoreId
                                . " AND filter_option_id = " . $currentOptions[$key]['id']
                            )
                    ));
                    $optionDefaultId = intval($this->getConnection()->fetchOne(
                        $this->getConnection()->select()
                            ->from($tableName, ['id'])
                            ->where(
                                "meta_key = '" . $fieldName
                                . "' AND store_id = 0 AND filter_option_id = " . $currentOptions[$key]['id']
                            )
                    ));

                    if (((array_key_exists($fieldName . '_is_default', $option)) && (array_key_exists($fieldName, $option)))
                        && (!(($currentStoreId > 0) && ($option[$fieldName . '_is_default'])))) {
                        $prepareDate = [
                            "filter_option_id" => $currentOptions[$key]['id'],
                            "store_id" => $currentStoreId,
                            "meta_key" => $fieldName,
                            "meta_value" => ($fieldName == $joinFields[1]) ? $option[$fieldName][0]['name'] : $option[$fieldName]
                        ];

                        if ($optionId) {
                            if ($currentOptions[$key][$fieldName] != $prepareDate["meta_value"]) {
                                $this->updateData($tableName, $prepareDate, $optionId, $fieldName, $currentStoreId, $option['id']);
                            }
                        } else {
                            if (!(array_key_exists('id', $option)) && ($currentStoreId)) {
                                $prepareDefaultDate = $prepareDate;
                                $prepareDefaultDate["store_id"] = 0;
                                $this->insertData($tableName, $prepareDefaultDate);
                            }

                            $prepareDate["filter_option_id"] = (array_key_exists('id', $option)) ? $option['id'] : $currentOptions[$key]['id'];
                            $this->insertData($tableName, $prepareDate);
                        }
                    } elseif ($optionDefaultId) {
                        $optionsDeleteIds[] = $optionId;
                        if ($fieldName == $joinFields[0]) {
                            $option[$joinFields[1] . '_is_default'] = 1;
                        }
                    } else {
                        $prepareDate = [
                            "filter_option_id" => $currentOptions[$key]['id'],
                            "store_id" => $currentStoreId,
                            "meta_key" => $fieldName,
                            "meta_value" => ($fieldName == $joinFields[1]) ? (array_key_exists($fieldName, $option)) ? $option[$fieldName][0]['name'] : '' : $option[$fieldName]
                        ];

                        if (!(array_key_exists('id', $option)) && ($currentStoreId)) {
                            $prepareDefaultDate = $prepareDate;
                            $prepareDefaultDate["store_id"] = 0;
                            $this->insertData($tableName, $prepareDefaultDate);
                        }

                        if ($currentOptions[$key][$fieldName] != $prepareDate["meta_value"]) {
                            if (array_key_exists('id', $option) && ($currentOptions[$key][$fieldName])) {
                                $this->updateData($tableName, $prepareDate, $optionId, $fieldName, $currentStoreId, $option['id']);
                            } else {
                                $this->insertData($tableName, $prepareDate);
                            }
                        } elseif (($optionId) && ($option[$fieldName . '_is_default'])) {
                            $optionsDeleteIds[] = $optionId;
                        }
                    }
                }
            }
        }

        $select = $this->getConnection()->delete($tableName, ['id IN (?)' => $optionsDeleteIds]);
    }
}
