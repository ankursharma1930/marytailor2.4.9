<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogPlus\Cron;

use Magefan\BlogPlus\Model\ResourceModel\RelatedPost;
use Magefan\BlogPlus\Model\Config;

/**
 * Class ProductTmp
 */
class ProductTmp
{
    /**
     * Path to enabled-blog module config
     */
    const XML_MODULE_BLOG_ENABLED = 'mfblog/general/enabled';

    /**
     * @var \Magento\Store\Model\StoreRepository
     */
    protected $storeRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory
     */
    protected $entityAttributeFactory;

    /**
     * @var RelatedPost
     */
    protected $relatedPost;

    /**
     * @var \Magefan\BlogPlus\Model\Config
     */
    private $config;

    /**
     * ProductTmp constructor.
     * @param RelatedPost $relatedPost
     * @param \Magento\Store\Model\StoreRepository $storeRepository
     * @param \Magento\Eav\Model\Entity\AttributeFactory $entityAttributeFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magefan\BlogPlus\Model\Config $config
     */
    public function __construct(
        \Magefan\BlogPlus\Model\ResourceModel\RelatedPost $relatedPost,
        \Magento\Store\Model\StoreRepository $storeRepository,
        \Magento\Eav\Model\Entity\AttributeFactory $entityAttributeFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        Config $config
    ) {
        $this->relatedPost = $relatedPost;
        $this->scopeConfig = $scopeConfig;
        $this->resource = $resource;
        $this->entityAttributeFactory = $entityAttributeFactory;
        $this->storeRepository = $storeRepository;
        $this->config = $config;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $enabled = $this->scopeConfig->getValue(self::XML_MODULE_BLOG_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $productTmpTable = $this->relatedPost->getTable('magefan_blog_product_tmp');

        $connection = $this->relatedPost->getConnection();
        $connection->truncateTable($productTmpTable);

        if ($enabled && $this->config->isAutoRelatedProductsEnabled()) {
            $stores = $this->storeRepository->getList();

            $attributeCodes = ['name', 'description', 'short_description'];
            $attributes = [];

            foreach ($attributeCodes as $attrCode) {
                $attributes[$attrCode] = $this->entityAttributeFactory->create()->loadByCode(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE, $attrCode);
            }

            foreach ($stores as $store) {
                $fields = [];
                $joins = [];

                foreach ($attributes as $attrCode => $attr) {
                    if ($attrCode != 'short_description' && ($attrId = $attr->getId())) {
                        $tableName = $this->relatedPost->getTable('catalog_product_entity_' . $attr->getBackendType());
                        $entityId = $this->resource->getConnection()->tableColumnExists($tableName, 'row_id') ? 'row_id' : 'entity_id';

                        $fields[] = 'IF(at_' . $attrCode . '.value_id > 0, at_' . $attrCode . '.value, at_' . $attrCode . '_default.value) AS `' . $attrCode . '`';
                        $joins[] = '
                        INNER JOIN `' . $tableName . '` AS `at_' . $attrCode . '_default`
                             ON (`at_' . $attrCode . '_default`.`'.$entityId.'` = `e`.`entity_id` )
                                AND (`at_' . $attrCode . '_default`.`attribute_id` = \'' . $attrId . '\')
                                AND `at_' . $attrCode . '_default`.`store_id` =  0
                        LEFT JOIN `' . $tableName . '` AS `at_' . $attrCode . '`
                             ON ( `at_' . $attrCode . '`.`'.$entityId.'` = `e`.`entity_id` )
                                AND ( `at_' . $attrCode . '`.`attribute_id` = \'' . $attrId . '\')
                                AND ( `at_' . $attrCode . '`.`store_id` = ' . $store->getId() . ')';
                    } else {
                        $fields[] = '"" as ' . $attrCode;
                    }
                }

                if (count($joins)) {

                    $sql = 'SELECT `e`.entity_id as product_id, ' . $store->getId() . ' as store_id,'
                        . implode(', ', $fields) . ' FROM `' . $this->relatedPost->getTable('catalog_product_entity') . '` AS `e` '
                        . implode(' ', $joins) . ' GROUP BY `product_id`';

                    $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
                    $connection->query('INSERT INTO ' . $productTmpTable . ' (product_id, store_id, title, description, short_description) ' . $sql);
                }
            }
        }
    }
}
