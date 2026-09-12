<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogImport\Model\Import;

use Magefan\Blog\Model\Import\AbstractImport;

/**
 * Blog import model
 */
class Amasty2 extends AbstractImport
{
    protected $_requiredFields = ['dbname', 'uname', 'dbhost'];

    public function execute()
    {
        $adapter = $this->getDbAdapter();

        $_pref = $this->getPrefix();

        $sql = 'SELECT * FROM '.$_pref.'amasty_blog_posts LIMIT 1';

        try {
            $adapter->query($sql)->execute();
        } catch (\Exception $e) {
            throw new \Exception(__('Amasty Blog Extension for Magento 2 not detected.'), 1);
        }

        $storeIds = array_keys($this->_storeManager->getStores(true));

        $categories = [];
        $oldCategories = [];

        /* Import categories */
        $sql = 'SELECT
                    t.category_id as old_id,
                    ts.name as title,
                    ts.url_key as identyfier,
                    ts.status as is_active,
                    t.sort_order as position,
                    ts.meta_title as meta_title,
                    ts.meta_tags as meta_keywords,
                    ts.meta_description as meta_description,
                    ts.description as content,
                    parent_id as parent_id
                FROM ' . $_pref . 'amasty_blog_categories t
                LEFT JOIN ' . $_pref . 'amasty_blog_categories_store ts on t.category_id = ts.category_id and store_id = 0';

        $result = $adapter->query($sql)->execute();

        foreach ($result as $data) {
            /* Prepare category data */


            /* Find store ids */
            $data['store_ids'] = [];
            $s_sql = 'SELECT `store_id` FROM 
                '.$_pref.'amasty_blog_categories_store WHERE `category_id` = '. ((int)$data['old_id']) . ";";
            $s_result =  $adapter->query($s_sql)->execute();
            foreach ($s_result as $s_data) {
                $data['store_ids'][] = $s_data['store_id'];
            }

            foreach ($data['store_ids'] as $key => $id) {
                if (!in_array($id, $storeIds)) {
                    unset($data['store_ids'][$key]);
                }
            }

            if (empty($data['store_ids']) || in_array(0, $data['store_ids'])) {
                $data['store_ids'] = 0;
            }

            $data['path'] = 0; ///!!!!

            $category = $this->_categoryFactory->create();
            try {
                /* Initial saving */
                $category->setData($data)->save();
                $this->_importedCategoriesCount++;
                $categories[$category->getId()] = $category;
                $oldCategories[$category->getOldId()] = $category;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($category);
                $this->_skippedCategories[] = $data['title'];
                $this->_logger->debug('Blog Category Import [' . $data['title'] . ']: '. $e->getMessage());
            }
        }

        /* Import tags */
        $tags = [];
        $oldTags = [];
        $existingTags = [];

        $sql = 'SELECT
                    t.tag_id as old_id,
                    ts.name as title,
                    ts.url_key as identyfier,
                    ts.meta_title as meta_title,
                    ts.meta_tags as meta_keywords,
                    ts.meta_description as meta_description,
                    1 as is_active
                FROM '.$_pref.'amasty_blog_tags t
                LEFT JOIN ' . $_pref . 'amasty_blog_tags_store ts on t.tag_id = ts.tag_id and store_id = 0';

        $result = $adapter->query($sql)->execute();
        foreach ($result as $data) {

            if (!$data['title']) {
                continue;
            }
            $data['title'] = trim($data['title']);
            if (is_numeric($data['title'])) {
                $data['title'] = 't' . $data['title'];
            }

            $data['store_ids'] = [0];

            try {
                /* Initial saving */
                if (!isset($existingTags[$data['title']])) {
                    $tag = $this->_tagFactory->create();
                    $tag->setData($data);

                    $currentTag = $tag->getCollection()
                        ->addFieldToFilter('title', $tag->getTitle())
                        ->setPageSize(1)
                        ->getFirstItem();
                    if ($currentTag->getId()) {
                        $tag = $currentTag;
                    } else {
                        $tag->save();
                    }
                    $this->_importedTagsCount++;
                    $tags[$tag->getId()] = $tag;
                    $oldTags[$tag->getOldId()] = $tag;
                    $existingTags[$tag->getTitle()] = $tag;
                } else {
                    $tag = $existingTags[$data['title']];
                    $oldTags[$data['old_id']] = $tag;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedTags[] = $data['title'];
                $this->_logger->debug('Blog Tag Import [' . $data['title'] . ']: '. $e->getMessage());
            }
        }

        /* Import authors */
        $authors = [];
        $oldAuthors = [];

        $sql = 'SELECT
                    t.author_id as old_id,
                    ts.name as name,
                    t.linkedin_profile as 	linkedin_page_url,
                    t.facebook_profile as facebook_page_url,
                    t.twitter_profile as 	twitter_page_url,
                    ts.job_title as role,
                    ts.description as content,
                    ts.short_description as short_content,
                    ts.url_key as identifier
                FROM '.$_pref.'amasty_blog_author t
                LEFT JOIN ' . $_pref . 'amasty_blog_author_store ts on t.author_id = ts.author_id and store_id = 0';

        $result = $adapter->query($sql)->execute();
        foreach ($result as $data) {

            if (!$data['name'] || !$data['old_id']) {
                continue;
            }

            $name = explode(' ', $data['name']);


            $data['firstname'] = trim($name[0]);
            unset($name[0]);

            if (count($name)) {
                $data['lastname'] = implode(' ', $name);
            } else {
                $data['lastname'] = '';
            }

            try {
                $author = $this->_authorFactory->create();
                $author->setData($data);
                $author->save();
                $this->_importedAuthorsCount++;
                $authors[$author->getId()] = $author;
                $oldAuthors[$author->getOldId()] = $author;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedAuthors[] = $data['firstname'] . ' ' . $data['lastname'];
                $this->_logger->debug('Blog Author Import [' . $data['firstname'] . ' ' . $data['lastname'] . ']: '. $e->getMessage());
            }
        }

        /* Import posts */
        $sql = 'SELECT * FROM '.$_pref.'amasty_blog_posts';
        $result = $adapter->query($sql)->execute();

        foreach ($result as $data) {


            /* Find post categories*/
            $postCategories = [];
            $c_sql = 'SELECT category_id  FROM '.
                $_pref.'amasty_blog_posts_category WHERE post_id = "'.$data['post_id'].'"';

            $c_result = $adapter->query($c_sql)->execute();
            foreach ($c_result as $c_data) {
                $oldId = $c_data['category_id'];
                if (isset($oldCategories[$oldId])) {
                    $id = $oldCategories[$oldId]->getId();
                    $postCategories[$id] = $id;
                }
            }
            /* Find post tags*/
            $postTags = [];
            $c_sql = 'SELECT tag_id  FROM '.
                $_pref.'amasty_blog_posts_tag WHERE post_id = "'.$data['post_id'].'"';

            $c_result = $adapter->query($c_sql)->execute();
            foreach ($c_result as $c_data) {
                $oldId = $c_data['tag_id'];
                if (isset($oldTags[$oldId])) {
                    $id = $oldTags[$oldId]->getId();
                    $postTags[$id] = $id;
                }
            }

            /* Find store ids */
            $data['store_ids'] = [];
            $s_sql = 'SELECT  `store_id`  FROM 
                '.$_pref.'amasty_blog_posts_store WHERE  `post_id` = '. ((int)$data['post_id']) . ";";
            $s_result =  $adapter->query($s_sql)->execute();
            foreach ($s_result as $s_data) {
                $data['store_ids'][] = $s_data['store_id'];
            }

            foreach ($data['store_ids'] as $key => $id) {
                if (!in_array($id, $storeIds)) {
                    unset($data['store_ids'][$key]);
                }
            }

            if (empty($data['store_ids']) || in_array(0, $data['store_ids'])) {
                $data['store_ids'] = [0];
            }

            /* Find post author */
            $oldAuthorId = $data['author_id'];
            if (isset($oldAuthors[$oldAuthorId])) {
                $data['author_id'] = $oldAuthors[$oldAuthorId]->getId();
            } else {
                $data['author_id'] = null;
            }

            if ($data['status'] > 0) {
                $data['status'] = 1;
            }

            /* Prepare post data */
            $data = [
                'old_id' => $data['post_id'],
                'store_ids' => $data['store_ids'],
                'title' => $data['title'],
                'meta_title' => $data['meta_title'],
                'meta_keywords' => $data['meta_tags'],
                'meta_description' => $data['meta_description'],
                'identifier' => $data['url_key'],
                'content_heading' => '',
                'content' => $data['full_content'],
                'short_content' => $data['short_content'],
                'creation_time' => $data['created_at'],
                'update_time' => $data['updated_at'],
                'publish_time' => $data['published_at'],
                'is_active' => (int)($data['status'] == 1),
                'categories' => $postCategories,
                'author_id' => $data['author_id'] ,
                'tags' => $postTags,
                'featured_img' => trim((string)$data['post_thumbnail'], '/'),
                'featured_list_img' => trim((string)$data['list_thumbnail'], '/'),
                'views_count' => $data['views'],
            ];


            $post = $this->_postFactory->create();
            try {
                /* Post saving */
                $post->setData($data)->save();

                $this->_importedPostsCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedPosts[] = $data['title'];
                $this->_logger->debug('Blog Post Import [' . $data['title'] . ']: '. $e->getMessage());
            }

            unset($post);
        }
        /* end */
        $adapter->getDriver()->getConnection()->disconnect();
    }
}
