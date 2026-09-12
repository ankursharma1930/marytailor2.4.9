<?php

namespace Mageside\Recipe\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Filter\FilterManager;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class UpgradeData
 * @package BodenkoVV\AskQuestion\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var FilterManager
     */
    protected $filter;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * UpgradeData constructor.
     * @param FilterManager $filter
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        FilterManager $filter,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->filter = $filter;
        $this->_customerRepository = $customerRepository;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.13', '<')) {
            $sql = $setup->getConnection()->select()
                ->from($setup->getTable('ms_recipe_writer'));

            $writers = $setup->getConnection()->fetchAll($sql);

            foreach ($writers as $writer) {
                if (!empty($writer['writer_url_key'])) {
                    $existUrlKey = $this->isExistDuplicateUrlKey($setup, $writer['writer_url_key']);
                    if (!$existUrlKey) {
                        continue;
                    }
                }

                $urlKey = $writer['writer_url_key'];
                if (!$urlKey) {
                    $customer = $this->_customerRepository->getById($writer['customer_id']);

                    if ($customer->getId()) {
                        $urlKey = $this->filter->translitUrl($customer->getFirstname() . '-' . $customer->getLastname());
                    }
                }

                $count = 1;
                do {
                    $newUrlKey = $urlKey;
                    if ($count >= 1) {
                        $newUrlKey = $urlKey . '-' . $count;
                    }

                    $count++;
                    $isDuplicateSaved = $this->isExistUrlKey($setup, $newUrlKey);
                } while ($isDuplicateSaved);

                $writer['writer_url_key'] = $newUrlKey;

                $setup->getConnection()->update(
                    $setup->getTable('ms_recipe_writer'),
                    $writer,
                    ['id = ?' => (int)$writer['id']]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.22') < 0) {
            $this->copyColumnDateOptionsTranslation(
                $setup,
                'ms_recipe_filter_options_varchar',
                'ms_recipe_filter_options',
                'label'
            );
            $this->copyColumnDateFilterTranslation(
                $setup,
                'ms_recipe_filter_varchar',
                'ms_recipe_filter',
                'type'
            );
            $this->copyColumnDateTranslation(
                $setup,
                'ms_recipe_varchar',
                'ms_recipe',
                'title'
            );
            $this->copyColumnDateTranslation(
                $setup,
                'ms_recipe_text',
                'ms_recipe',
                'ingredients'
            );
            $this->copyColumnDateTranslation(
                $setup,
                'ms_recipe_text',
                'ms_recipe',
                'short_description'
            );
            $this->copyColumnDateTranslation(
                $setup,
                'ms_recipe_text',
                'ms_recipe',
                'method'
            );
        }

        if (version_compare($context->getVersion(), '1.3.1') < 0) {
            $this->copyColumnDateOptionsTranslation(
                $setup,
                'ms_recipe_filter_options_varchar',
                'ms_recipe_filter_options',
                'option_image'
            );
        }

        $setup->endSetup();
    }

    /**
     * @param $setup
     * @param $toTableName
     * @param $fromTableName
     * @param $fromColumn
     */
    public function copyColumnDateTranslation($setup, $toTableName, $fromTableName, $fromColumn)
    {
        $fromTable = $setup->getTable($fromTableName);
        $toTable = $setup->getTable($toTableName);

        $select = $setup->getConnection()->select()
            ->from(
                $fromTable,
                [
                    'recipe_id',
                    'store_id'      => new \Zend_Db_Expr(0),
                    'meta_key'      => new \Zend_Db_Expr($setup->getConnection()->quote($fromColumn)),
                    'meta_value'    => new \Zend_Db_Expr($fromColumn)
                ]
            );

        $query = $setup->getConnection()
            ->insertFromSelect(
                $select,
                $toTable,
                ['recipe_id', 'store_id', 'meta_key', 'meta_value'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
            );
        $setup->getConnection()->query($query);

        $setup->getConnection()
            ->dropColumn(
                $fromTable,
                $fromColumn
            );
    }

    /**
     * @param $setup
     * @param $toTableName
     * @param $fromTableName
     * @param $fromColumn
     */
    public function copyColumnDateFilterTranslation($setup, $toTableName, $fromTableName, $fromColumn)
    {
        $fromTable = $setup->getTable($fromTableName);
        $toTable = $setup->getTable($toTableName);
        $storeId = 0;

        $select = $setup->getConnection()
            ->select()
            ->from($fromTable, ['id as filter_id', $fromColumn . ' as meta_value']);
        $query = $setup->getConnection()
            ->insertFromSelect(
                $select,
                $toTable,
                ['filter_id', 'meta_value'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
            );
        $setup->getConnection()->query($query);

        $setup->getConnection()
            ->update($toTable, ['meta_key' => $fromColumn, 'store_id' => $storeId], 'meta_key IS NULL');

        $setup->getConnection()
            ->dropColumn(
                $fromTable,
                $fromColumn
            );
    }

    /**
     * @param $setup
     * @param $toTableName
     * @param $fromTableName
     * @param $fromColumn
     */
    public function copyColumnDateOptionsTranslation($setup, $toTableName, $fromTableName, $fromColumn)
    {
        $fromTable = $setup->getTable($fromTableName);
        $toTable = $setup->getTable($toTableName);
        $storeId = 0;

        $select = $setup->getConnection()
            ->select()
            ->from($fromTable, ['id as filter_id', $fromColumn . ' as meta_value']);
        $query = $setup->getConnection()
            ->insertFromSelect(
                $select,
                $toTable,
                ['filter_option_id', 'meta_value'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
            );
        $setup->getConnection()->query($query);

        $setup->getConnection()
            ->update($toTable, ['meta_key' => $fromColumn, 'store_id' => $storeId], 'meta_key IS NULL');

        $setup->getConnection()
            ->dropColumn(
                $fromTable,
                $fromColumn
            );
    }
}
