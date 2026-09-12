<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogImport\Model\Import;

/**
 * Aw import model
 */
class Em extends \Magefan\Blog\Model\Import\AbstractImport
{
    protected $entityTypeId = [];
    protected $attributes = [];
    protected $pref;
    protected $_importedCommentsCount;
    protected $_skippedComments = [];


    public function execute()
    {
        $con = $this->_connect = mysqli_connect(
            $this->getData('dbhost'),
            $this->getData('uname'),
            $this->getData('pwd'),
            $this->getData('dbname')
        );
        if (mysqli_connect_errno()) {
            throw new \Exception("Failed connect to wordpress database", 1);
        }

        mysqli_set_charset($con, "utf8");

        $_pref = mysqli_real_escape_string($con, $this->getData('prefix'));

        $sql = 'SELECT * FROM ' . $_pref . 'blog_category_entity LIMIT 1';
        try {
            $this->_mysqliQuery($sql);
        } catch (\Exception $e) {
            throw new \Exception(__('EM Blog Extension not detected . '), 1);
        }

        /* Import categories */

        $categories = [];
        $oldCategories = [];
        $categoryMapping = [
            'name' => 'title',
            'description' => 'content',
            //'image' => '',
            'page_title' => 'meta_title',
            'meta_keywords' => 'meta_keywords',
            'meta_description' => 'meta_description',
            'is_active' => 'is_active',
            'url_key' => 'identifier',
            //'show_image' => '',
            //'display_mode' => '',
            //'cms_block' => '',
            //'is_anchor' => '',
            //'custom_use_parent_settings' => '',
            //'custom_design' => '',

            'custom_design_from' => 'custom_theme_from',
            'custom_design_to' => 'custom_theme_to',
            'custom_layout' => 'custom_layout',
            'custom_layout_update_xml' => 'custom_layout_update_xml',
            //'created_at' => '',
            //'updated_at' => '',
            //'level' => '',
            //'children_count' => '',
            //'path' => '',
            'position' => 'position',
        ];

        $sql = "SELECT * FROM `" . $_pref . "blog_category_entity`";

        $result = $this->_mysqliQuery($sql);
        while ($entityData = mysqli_fetch_assoc($result)) {


            /* Prepare category data */
            $data = [];
            $path = explode('/', $entityData['path']);
            $cPath = count($path);
            if ($cPath < 3) {
                //root categories;
                continue;
            }
            $data['parent_id'] = $path[$cPath - 2];

            $data['store_ids'] = [$this->getStoreId()];
            $data['path'] = 0;

            foreach ($categoryMapping as $from => $to) {
                $data[$to] = $this->getCategoryData($entityData, $from);
            }

            $category = $this->_categoryFactory->create();
            try {
                /* Initial saving */
                $category->load($data['identifier'], 'identifier');
                if (!$category->getId()) {
                    $category->setData($data)->save();
                }
                $this->_importedCategoriesCount++;
                $categories[$category->getId()] = $category;
                $oldCategories[$entityData['entity_id']] = $category;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($category);
                $this->_skippedCategories[] = $data['title'];
                $this->_logger->debug('Blog Category Import [' . $data['title'] . ']: ' .  $e->getMessage());
            }
        }


        /* Reindexing parent categories */
        foreach ($categories as $ct) {
            if ($oldParentId = $ct->getData('parent_id')) {
                if (isset($oldCategories[$oldParentId])) {
                    $ct->setPath(
                        $parentId = $oldCategories[$oldParentId]->getId()
                    );
                }
            }
        }
        for ($i = 0; $i < 4; $i++) {
            $changed = false;
            foreach ($categories as $ct) {
                if ($ct->getPath()) {
                    $parentId = explode('/', $ct->getPath())[0];
                    $pt = $categories[$parentId];
                    if ($pt->getPath()) {
                        $ct->setPath($pt->getPath() . '/'. $ct->getPath());
                        $changed = true;
                    }
                }
            }
            if (!$changed) {
                break;
            }
        }

        foreach ($categories as $ct) {
            /* Final saving */
            $ct->save();
        }


        /* Import tags */
        $tags = [];
        $oldTags = [];
        $sql = 'SELECT
                    id as old_id,
                    name as title,
                    tag_identifier as identifier
                FROM '.$_pref.'blog_tag';
        $result = $this->_mysqliQuery($sql);
        while ($data = mysqli_fetch_assoc($result)) {
            /* Prepare tag data */
            foreach (['title', 'identifier'] as $key) {
                $data[$key] = mb_convert_encoding($data[$key], "UTF-8", "ISO-8859-1");
            }
            $data['identifier'] = trim(strtolower($data['identifier']));
            if (strlen($data['identifier']) == 1) {
                $data['identifier'] .= $data['identifier'];
            }
            $tag = $this->_tagFactory->create();
            try {
                /* Initial saving */
                $tag->load($data['identifier'], 'identifier');
                if (!$tag->getId()) {
                    $tag->setData($data)->save();
                }
                $this->_importedTagsCount++;
                $tags[$tag->getId()] = $tag;
                $oldTags[$data['old_id']] = $tag;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($tag);
                $this->_skippedTags[] = $data['title'];
                $this->_logger->debug('Blog Tag Import [' . $data['title'] . ']: '. $e->getMessage());
            }
        }


        /* Import posts */
        $posts = [];
        $oldPosts = [];
        $postMapping = [
            //'allow_comment' => '',
            //'author_id' => '',
            'created_at' => 'creation_time',
            //'custom_design' => '',
            'custom_design_from' => 'custom_theme_from',
            'custom_design_to' => 'custom_theme_to',
            'custom_layout' => 'custom_layout',
            'custom_layout_update_xml' => 'custom_layout_update_xml',
            'image' => 'featured_img',
            'post_content' => 'content',
            'post_content_heading' => 'content_heading',
            'post_identifier' => 'identifier',
            'post_intro' => 'short_content',
            'post_meta_description' => 'meta_description',
            'post_meta_keywords' => 'meta_keywords',
            'status' => 'is_active',
            'title' => 'title',
            'updated_at' => 'update_time',

            'created_at' => 'publish_time',

        ];

        $sql = 'SELECT * FROM ' . $_pref . 'blog_post_entity';
        $result = $this->_mysqliQuery($sql);
        while ($entityData = mysqli_fetch_assoc($result)) {


            /* Find post categories*/
            $postCategories = [];
            $sql = 'SELECT category_id  FROM ' . $_pref . 'blog_category_post WHERE post_id = "' . $entityData['entity_id'] . '"';
            $result2 = $this->_mysqliQuery($sql);
            while ($data2 = mysqli_fetch_assoc($result2)) {
                $oldTermId = $data2['category_id'];
                if (isset($oldCategories[$oldTermId])) {
                    $postCategories[] = $oldCategories[$oldTermId]->getId();
                }
            }

            /* find post tags*/
            $postTags = [];
            $sql = 'SELECT tag_id  FROM ' . $_pref . 'blog_tag_post WHERE post_id = "' . $entityData['entity_id'] . '"';
            $result2 = $this->_mysqliQuery($sql);
            while ($data2 = mysqli_fetch_assoc($result2)) {
                $oldTermId = $data2['tag_id'];
                if (isset($oldTags[$oldTermId])) {
                    $postTags[] = $oldTags[$oldTermId]->getId();
                }
            }

            $data = [
                'categories' => $postCategories,
                'tags' => $postTags,

            ];

            /* Find store ids */
            $data['store_ids'] = [$this->getStoreId()];

            /* Prepare post data */
            foreach ($postMapping as $from => $to) {
                $data[$to] = $this->getPostData($entityData, $from);
            }

            $post = $this->_postFactory->create();
            try {
                /* Post saving */
                $post->setData($data)->save();
                $this->_importedPostsCount++;
                $posts[$post->getId()] = $post;
                $oldPosts[$entityData['entity_id']] = $post;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedPosts[] = $data['title'];
                $this->_logger->debug('Blog Post Import [' . $data['title'] . ']: ' .  $e->getMessage());
            }
            unset($post);
        }

        /* Import Comments */
        $ob = \Magento\Framework\App\ObjectManager::getInstance();
        $comments = [];
        $oldComments = [];
        $sql = 'SELECT
                    id as old_id,
                    comment_content as `text`,
                    parent_id as parent_id,
                    `time` as creation_time,
                    `time` as update_time,
                    post_id as post_id,
                    status_comment as status,
                    username as author_nickname,
                    email as    author_email
                FROM '.$_pref.'blog_comment';
        $result = $this->_mysqliQuery($sql);
        while ($data = mysqli_fetch_assoc($result)) {
            /* Prepare comment data */

            $comment = $ob->create(\Magefan\Blog\Model\Comment::class);
            try {

                if (!isset($oldPosts[$data['post_id']])) {
                    // new post does not exist
                    continue;
                }

                if (isset($oldPosts[$data['post_id']])) {
                    $data['post_id'] = $oldPosts[$data['post_id']]->getId();
                }

                /* Initial saving */
                $comment->setData($data);

                /* Change status */
                switch ($comment->getStatus()) {
                    case 0:
                        $comment->setStatus(2);
                        break;
                    case 1:
                        $comment->setStatus(0);
                        break;
                    case 2:
                        $comment->setStatus(1);
                        break;
                }

                $comment->save();
                $this->_importedCommentsCount++;
                $comments[$comment->getId()] = $tag;
                $oldComments[$comment->getOldId()] = $tag;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($comment);
                $this->_skippedComments[] = $data['author_nickname'];
                $this->_logger->debug('Blog Comment Import [' . $data['author_nickname'] . ']: '. $e->getMessage());
            }
        }

        /* Reindexing parent commnents */
        foreach ($comments as $ct) {
            if ($oldParentId = $ct->getData('parent_id')) {
                if (isset($oldComments[$oldParentId])) {
                    $ct->setPath(
                        $parentId = $oldComments[$oldParentId]->getId()
                    );
                    $ct->save();
                }
            }
        }

        /* end */
        mysqli_close($con);
    }


    protected function getEavData($entityData, $attributeCode, $entityTypeCode)
    {
        $entityId = $entityData['entity_id'];

        if (!isset($this->entityTypeId[$entityTypeCode])) {
            $sql = "SELECT * FROM `" .  $this->pref . "eav_entity_type` WHERE `entity_type_code` = '" . $entityTypeCode . "' LIMIT 1";
            $result = $this->_mysqliQuery($sql);
            $this->entityTypeId[$entityTypeCode] = mysqli_fetch_assoc($result)['entity_type_id'];
        }
        $entityTypeId = $this->entityTypeId[$entityTypeCode];

        if (!isset($this->attributes[$entityTypeCode.$attributeCode])) {
            $sql = "SELECT * FROM `" .  $this->pref . "eav_attribute` WHERE `entity_type_id` = '" .  $entityTypeId . "' and attribute_code='" . $attributeCode . "' LIMIT 1";
            $result = $this->_mysqliQuery($sql);
            $this->attributes[$entityTypeCode.$attributeCode] = mysqli_fetch_assoc($result);
        }
        $attribute = $this->attributes[$entityTypeCode.$attributeCode];

        if ($attribute['backend_type'] == 'static') {
            return $entityData[$attributeCode];
        } else {
            $sql = "SELECT * FROM `" .  $this->pref . $entityTypeCode . "_entity_" . $attribute['backend_type'] . "` WHERE `entity_id` = '" . $entityId . "' and `attribute_id` = '" . $attribute['attribute_id'] . "' and store_id = 0 LIMIT 1";


            $result = $this->_mysqliQuery($sql);
            $data = mysqli_fetch_assoc($result);

            return isset($data['value']) ? $data['value'] : null;
        }
    }

    protected function getCategoryData($entityData, $attributeCode)
    {
        return $this->getEavData($entityData, $attributeCode, 'blog_category');
    }

    protected function getPostData($entityData, $attributeCode)
    {
        return $this->getEavData($entityData, $attributeCode, 'blog_post');
    }
}
