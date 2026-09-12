<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var RequestInterface
     */
    protected $_request;

    protected function _construct()
    {
        $this->_init(
            \Mageside\Recipe\Model\Recipe\Filter\Options::class,
            \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options::class
        );
    }

    /**
     * Collection constructor.
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    public function _beforeLoad()
    {
        $this->joinOptionsData();

        parent::_beforeLoad();
    }

    /**
     * @param $id
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function joinOptionData($id)
    {
        $currentStoreId = $this->_storeManager->getStore()->getId();
        $joinFields = [
            'label' => 'varchar',
            'option_image' => 'varchar',
        ];

        foreach ($joinFields as $fieldName => $backendType) {
            $tableName = $this->getTable('ms_recipe_filter_options_' . $backendType);

            $this->getSelect()
                ->joinLeft(
                    ["ats_{$fieldName}" => $tableName],
                    "main_table.id = ats_{$fieldName}.filter_option_id
                    AND ats_{$fieldName}.meta_key = \"{$fieldName}\"
                    AND ats_{$fieldName}.store_id = 0",
                    null
                )
                ->joinLeft(
                    ["ats_{$fieldName}_store" => $tableName],
                    "main_table.id = ats_{$fieldName}_store.filter_option_id 
                    AND ats_{$fieldName}_store.meta_key = \"{$fieldName}\" 
                    AND ats_{$fieldName}_store.store_id = $currentStoreId",
                    null
                )
                ->where('main_table.filter_id = ' . $id)
                ->columns([
                    $fieldName => new \Zend_Db_Expr(
                        "IFNULL(ats_{$fieldName}_store.meta_value, ats_{$fieldName}.meta_value)"
                    )
                ]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function joinOptionsData()
    {
        $currentStoreId = $this->_request->getParam('store');
        if (!$currentStoreId) {
            $currentStoreId = 0;
        }
        $joinFields = [
            'label' => 'varchar',
            'option_image' => 'varchar',
        ];

        foreach ($joinFields as $fieldName => $backendType) {
            $tableName = $this->getTable('ms_recipe_filter_options_' . $backendType);

            $this->getSelect()
                ->joinLeft(
                    ["at_{$fieldName}" => $tableName],
                    "main_table.id = at_{$fieldName}.filter_option_id 
                    AND at_{$fieldName}.meta_key=\"{$fieldName}\" 
                    AND at_{$fieldName}.store_id = 0",
                    null
                )
                ->joinLeft(
                    ["at_{$fieldName}_store" => $tableName],
                    "main_table.id = at_{$fieldName}_store.filter_option_id 
                    AND at_{$fieldName}_store.meta_key=\"{$fieldName}\" 
                    AND at_{$fieldName}_store.store_id = $currentStoreId",
                    null
                )
                ->columns([
                    $fieldName => new \Zend_Db_Expr(
                        "IFNULL(at_{$fieldName}_store.meta_value, at_{$fieldName}.meta_value)"
                    ),
                    $fieldName . '_is_default' => new \Zend_Db_Expr(
                        "IF(at_{$fieldName}_store.meta_value IS NULL, 1, 0)"
                    ),
                    $fieldName . '_default_value' => "at_{$fieldName}.meta_value",
                ]);
        }

        return $this;
    }
}
