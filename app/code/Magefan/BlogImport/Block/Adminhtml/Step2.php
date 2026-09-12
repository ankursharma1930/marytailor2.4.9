<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogImport\Block\Adminhtml;

use Magento\Store\Model\ScopeInterface;

/**
 * Step1 import block
 */
class Step2 extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magefan\BlogImport\Model\Import\Csv
     */
    protected $csv;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magefan\Blog\Model\ResourceModel\Post $resource
     * @param \Magefan\BlogImport\Model\Import\Csv $csv
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magefan\Blog\Model\ResourceModel\Post $resource,
        \Magefan\BlogImport\Model\Import\Csv $csv,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->csv =$csv;
        $this->resource = $resource;
    }

    public function getRows()
    {
        return $this->csv->getRows(15);
    }

    public function getColumns()
    {
        $tableInfo = $this->resource->getConnection()->describeTable(
            $this->resource->getMainTable()
        );

        $columns = ['' => 'Do not import'];
        foreach ($tableInfo as $field => $info) {
            if ($field === 'post_id') {
                $columns['existing_posts'] = __('ID of Existing Post');
            } else {
                $columns[$field] = ucwords(str_replace('_', ' ', $field));
            }
        }
        $columns['categories'] = 'Categories';
        $columns['tags'] = 'Tags';
        $columns['store_ids'] = 'Store IDs';
        $columns['related_posts'] = 'Related Posts';
        $columns['related_products'] = 'Related Products';

        return $columns;
    }
}
