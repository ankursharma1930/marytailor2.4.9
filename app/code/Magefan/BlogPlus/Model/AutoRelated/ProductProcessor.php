<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Model\AutoRelated;

/**
 * Class ProductProcessor
 */
class ProductProcessor extends AbstractProcessor
{
    /**
     * @var string
     */
    protected $autoRelatedTable = 'magefan_blog_post_relatedproduct';

    /**
     * @var string
     */
    protected $autoRelatedLogTable = 'magefan_blog_post_relatedproduct_log';

    /**
     * @param $post
     * @return array
     */
    protected function getRelatedIds($post)
    {
        return $post->getRelatedProducts()->getAllIds();
    }

    /**
     * @param \Magefan\Blog\Model\Post $post
     * @return mixed
     */
    protected function getAutoRelatedIds(
        \Magefan\Blog\Model\Post $post
    ) {
        $collection = $this->postCollectionFactory->create();
        $resource = $collection->getResource();
        $connection = $collection->getResource()->getConnection();

        $vars = [
            'description' => $this->extractKeywords($post->getContent()),
            'title' => $this->extractKeywords($post->getTitle())
        ];

        $where = '(0
          + (MATCH (description) AGAINST (:description)) * 1
          + (MATCH (description) AGAINST (:title)) * 2
          + (MATCH (title) AGAINST (:description)) * 2
          + (MATCH (title) AGAINST (:title)) * 3) > 5';

        $select = $connection->select()
            ->from(['main_table' => $resource->getTable('magefan_blog_product_tmp')])
            ->columns(['search_rate' => '(0
              + (MATCH (description) AGAINST (:description)) * 1
              + (MATCH (description) AGAINST (:title)) * 2
              + (MATCH (title) AGAINST (:description)) * 2
              + (MATCH (title) AGAINST (:title)) * 3)'])
            ->where($where)
            ->group('main_table.product_id')
            ->order('search_rate ' .  \Magento\Framework\Api\SortOrder::SORT_DESC)
            ->limit($this->limit);

        $result = $connection->fetchAll($select, $vars);

        $ids = [];
        if (count($result)) {
            foreach ($result as $item) {
                $ids[] = $item['product_id'];
            }
        }
        return $ids;
    }

    /**
     * Retrieve tru if can generate auto related items
     * @return bool
     */
    protected function autoRelatedEnabled()
    {
        return $this->config->isAutoRelatedProductsEnabled();
    }

    /**
     * Get array of black words from Abstract Processor and from admin panel and join two arrays
     * @return array
     */
    protected function getIgnoredWords()
    {
        return array_merge(
            parent::getIgnoredWords(),
            $this->config->getAutoRelatedProductsBlackWords()
        );
    }
}
