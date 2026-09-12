<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Recipe;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;
use Mageside\Recipe\Helper\Config;
use Mageside\Recipe\Model\FileUploader;
use Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options;
use Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Store as FilterStore;
use Mageside\Recipe\Model\ResourceModel\Recipe\Filter\StoreFactory as FilterStoreResourceFactory;

class Filter extends AbstractDb
{
    /**
     * @var Filter\Options
     */
    protected $_optionsResourceModel;

    /**
     * @var FileUploader
     */
    protected $_fileUploader;

    /**
     * Store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $_helper;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var FilterStoreResourceFactory
     */
    protected $resourceStoreFactory;

    /**
     * Filter constructor.
     * @param Context $context
     * @param Options $optionsResourceModel
     * @param FileUploader $fileUploader
     * @param Config $helper
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param FilterStoreResourceFactory $resourceStoreFactory
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        Options $optionsResourceModel,
        FileUploader $fileUploader,
        Config $helper,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        FilterStoreResourceFactory $resourceStoreFactory,
        $connectionName = null
    ) {
        $this->_optionsResourceModel = $optionsResourceModel;
        $this->_fileUploader = $fileUploader;
        $this->_helper = $helper;
        $this->storeManager = $storeManager;
        $this->_request = $request;
        $this->resourceStoreFactory = $resourceStoreFactory;
        parent::__construct($context, $connectionName);
    }

    protected function _construct()
    {
        $this->_init('ms_recipe_filter', 'id');
    }

    /**
     * @param AbstractModel $object
     * @return AbstractDb
     */
    protected function _beforeSave(AbstractModel $object)
    {
        /** @var \Mageside\Recipe\Model\Recipe\Filter $object */
        $origData = $object->getData();
        foreach ($origData as $key => $value) {
            if (empty($value)) {
                unset($origData[$key]);
            }
        }

        $filterCode = $this->_helper->prepareSlug($object->getCode());
        $object->addData($origData);
        unset($origData['options']);
        $object->setCode($filterCode);

        return parent::_beforeSave($object);
    }

    /**
     * @param AbstractModel $object
     * @return AbstractDb
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _afterSave(AbstractModel $object)
    {
        $this->saveRelatedOptions($object);
        $this->saveRecipeFilterData($object);
        $this->_optionsResourceModel->saveFilterOptionsData($object);
        $this->saveStore($object);

        return parent::_afterSave($object);
    }

    /**9
     * @param $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveRelatedOptions($object)
    {
        if (!$object->getOptions()) {
            return $this;
        }

        $fieldId = $object->getId();
        $options = [];
        $optionsUpdate = [];
        $optionsDeleteIds = [];
        $optionIds = $object->loadOptionsByFilterId($fieldId)->getAllIds();
        $optionIds = array_flip($optionIds);

        foreach ($object->getOptions() as $option) {
            if (!empty($this->getImageName($option))) {
                $this->_fileUploader->moveFileFromTmp($this->getImageName($option));
            }
            if (!empty($option['delete']) && $option['delete'] === 'true' && !empty($option['id'])) {
                $optionsDeleteIds[] = $option['id'];
            } elseif (!empty($option['id'])) {
                $optionsUpdate[] = [
                    'id'            => $option['id'],
                    'option_image'  => $this->getImageName($option),
                    'slug'          => $this->_helper->prepareSlug($option['label'])
                ];
                unset($optionIds[$option['id']]);
            } else {
                $options[] = [
                    'filter_id'     => $fieldId,
                    'slug'          => $this->_helper->prepareSlug($option['label'])
                ];
            }
        }
        $optionsDeleteIds = array_flip($optionIds);

        if (!empty($optionsDeleteIds)) {
            $this->_optionsResourceModel->deleteOptions($optionsDeleteIds);
        }

        if (!empty($optionsUpdate)) {
            $this->_optionsResourceModel->updateOptions($optionsUpdate);
        }

        if (!empty($options)) {
            $this->_optionsResourceModel->saveOptions($options);
        }

        return $this;
    }

    /**
     * @param $option
     * @return string
     */
    public function getImageName($option)
    {
        if (!empty($option['option_image'])) {
            return $option['option_image'][0]['name'];
        } else {
            return '';
        }
    }

    /**
     * @param AbstractModel $object
     * @return AbstractDb|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    public function _afterLoad(AbstractModel $object)
    {
        $this->joinOptionData($object);
        $this->loadStoreData($object);
        parent::_afterLoad($object);
    }

    /**
     * @param $object
     * @return mixed
     * @throws \Zend_Db_Select_Exception
     */
    public function joinOptionData($object)
    {
        if ($object->getId()) {
            $currentFilterId = $object->getId();
            $currentStoreId = $this->_request->getParam('store');
            if (!$currentStoreId) {
                $currentStoreId = 0;
            }
            $connection = $this->getConnection(ResourceConnection::DEFAULT_CONNECTION);
            $selects = [];
            $joinFields = [
                'varchar'
            ];
            foreach ($joinFields as $dateType) {
                $selects[] = $connection->select()
                    ->from(["attrtable" => $this->getTable("ms_recipe_filter_{$dateType}")])
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->joinLeft(
                        ["store_attrtable" => $this->getTable("ms_recipe_filter_{$dateType}")],
                        "attrtable.meta_key = store_attrtable.meta_key and store_attrtable.filter_id = "
                        . strval($currentFilterId)
                        . " and store_attrtable.store_id = " . $currentStoreId,
                        null
                    )
                    ->where("attrtable.filter_id = "
                        . strval($currentFilterId)
                        . " and attrtable.store_id = 0")
                    ->columns([
                        "meta_key" => "attrtable.meta_key",
                        "meta_value" => new \Zend_Db_Expr(
                            "IFNULL(store_attrtable.meta_value, attrtable.meta_value)"
                        ),
                        "is_default" => new \Zend_Db_Expr(
                            "if(store_attrtable.meta_value IS NULL, 1, 0)"
                        ),
                        "default_value" => "attrtable.meta_value"
                    ]);
            }
            $attribUnion = $connection->select()->union($selects, \Magento\Framework\DB\Select::SQL_UNION_ALL);
            $data = $connection->fetchAll($attribUnion);

            foreach ($data as $title => $dat) {
                $dataAll[$dat['meta_key']] = $dat['meta_value'];
                $dataAll[$dat['meta_key'] . '_is_default'] = $dat['is_default'];
                $dataAll[$dat['meta_key'] . '_default_value'] = $dat['default_value'];
            }
        }

        if (!empty($data)) {
            $object->addData(isset($dataAll) ? $dataAll : []);
        }

        return $object;
    }

    /**
     * @param $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function saveStore($object)
    {
        if ($filterId = $object->getId()) {
            /** @var FilterStore $resourceStores */
            $resourceStores = $this->resourceStoreFactory->create();
            $resourceStores->clearStoresById($filterId);

            if ($stores = $object->getData('store_view')) {
                $storeData = [];
                if (in_array(0, $stores)) {
                    $storeData[] = [
                        'filter_id' => $filterId,
                        'store_id'  => 0
                    ];
                } else {
                    foreach ($stores as $id) {
                        $storeData[] = [
                            'filter_id' => $filterId,
                            'store_id'  => $id
                        ];
                    }
                }

                if (!empty($storeData)) {
                    $resourceStores->saveChoosedStore($storeData);
                }
            }
        }

        return $this;
    }

    /**
     * @param $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loadStoreData($object)
    {
        if ($filterId = $object->getId()) {
            /** @var FilterStore $resourceStores */
            $resourceStores = $this->resourceStoreFactory->create();
            $stores = $resourceStores->getFilterStores($filterId);

            if (empty($stores)) {
                $stores = ['0'];
            }
            $object->setData('store_view', $stores);
        }

        return $this;
    }

    /**
     * @param $object
     * @return $this
     */
    public function saveRecipeFilterData($object)
    {
        if (!$object->getData()) {
            return $this;
        }

        $currentFilterId = $object->getId();
        $currentStoreId = $object->getData('store_id');
        $joinFields = [
            'type' => 'varchar'
        ];

        if ($currentFilterId) {
            $connection = $this->getConnection(ResourceConnection::DEFAULT_CONNECTION);
            $recipeData = $object->getData();
            $useDefault = $this->_request->getParam('use_default');
            $recordDeleteIds = [];
            foreach ($joinFields as $fieldName => $backendType) {
                $tableName = $this->getTable('ms_recipe_filter_' . $backendType);
                $select = $connection->select()
                    ->from($tableName, ['id'])
                    ->where('meta_key = ?', $fieldName)
                    ->where('store_id = ?', $currentStoreId)
                    ->where('filter_id = ?', $currentFilterId);
                $recordId = $connection->fetchOne($select);

                if ($currentStoreId > 0 && !$useDefault['type'] || $currentStoreId == 0) {
                    $prepareDate = [
                        "meta_key"      => $fieldName,
                        "store_id"      => $currentStoreId,
                        "filter_id"     => $currentFilterId,
                        "meta_value"    => $recipeData[$fieldName]
                    ];

                    if ($recordId) {
                        $result = $connection->update(
                            $tableName,
                            $prepareDate,
                            [
                                'meta_key = ?'  => $fieldName,
                                'store_id = ?'  => $currentStoreId,
                                'filter_id = ?' => $currentFilterId
                            ]
                        );
                    } else {
                        $result = $connection->insert(
                            $tableName,
                            $prepareDate
                        );
                    }
                } elseif ($currentStoreId > 0 && $useDefault['type'] && $recordId) {
                    $recordDeleteIds[] = $recordId;
                }
                if (!empty($recordDeleteIds)) {
                    $connection->delete($tableName, ['id IN (?)' => $recordDeleteIds]);
                }
            }
        }
    }
}
