<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
 
namespace Magefan\BlogImport\Model\Import;

/**
 * Drupal import model
 * Copy image files
 * /sites/default/files/styles/blog_landing/public/ -> /pub/media/magefan_blog/
 * /sites/default/files/inline-images/ -> /pub/media/wysiwyg/inline-images/
 */
class Drupal extends \Magefan\Blog\Model\Import\AbstractImport
{
    protected $_requiredFields = ['dbname', 'uname', 'pwd', 'dbhost'];

    public function execute()
    {
        $adapter = $this->getDbAdapter();
        $_pref = $this->getPrefix();

        $sql = 'SELECT * FROM '.$_pref.'node_field_data LIMIT 1';

        try {
            $adapter->query($sql)->execute();
        } catch (\Exception $e) {
            throw new \Exception(__('Drupal database tables not detected.'), 1);
        }

        /* Import posts */
        $donePosts = [];
        $sql = 'SELECT 
                main.nid as old_id,
                main.title,
                main.status,
                main.created,
                main.changed,
                meta.field_meta_tags_value as meta_value,
                path_alias.alias as url_key,
                body.body_value as content,
                fm.uri as featured_img

            FROM '.$_pref.'node_field_data main
            LEFT JOIN '.$_pref.'node__field_meta_tags meta on main.nid = meta.entity_id
            LEFT JOIN '.$_pref.'node__body body on main.nid = body.entity_id
            LEFT JOIN '.$_pref.'path_alias path_alias on path_alias.path = CONCAT("/node/", meta.entity_id) 
            LEFT JOIN '.$_pref.'file_usage fu on fu.id = main.nid and fu.type="node" and fu.module="file"
            LEFT JOIN '.$_pref.'file_managed fm on fm.fid = fu.fid
            WHERE main.type = "blog"
            ';

        $result = $adapter->query($sql)->execute();

        foreach ($result as $data) {

            if (isset($donePosts[$data['old_id']])) {
                continue;
            }
            $donePosts[$data['old_id']] = $data['old_id'];

            /* Find post store */
            $data['store_ids'] = [$this->getStoreId()];

            /* Find post author */
            $postAuthorId = null;
            try {
                $metaData = unserialize($data['meta_value']);
            } catch (\Exception $e) {
                $metaData = [];
            }

            if ($data['status'] > 0) {
                $data['status'] = 1;
            }

            /* Prepare post data */
            $data = [
                'old_id' => $data['old_id'],
                'store_ids' => $data['store_ids'],
                'title' => $data['title'],

                'meta_title' => isset($metaData['title']) ? $metaData['title'] : '',
                //'meta_keywords' => isset($metaData['title']) ? $metaData['title'] : '',
                'meta_description' => isset($metaData['description']) ? $metaData['description'] : '',

                'og_title' => isset($metaData['og_title']) ? $metaData['og_title'] : '',
                'og_description' => isset($metaData['og_description']) ? $metaData['og_description'] : '',

                'identifier' => str_replace('/blog/', '', $data['url_key']),
                'content_heading' => '',
                'content' => $this->parseContent($data['content']),
                //'short_content' => $data['short_content'],
                'creation_time' => $data['created'],
                'update_time' => $data['changed'],
                'publish_time' => $data['created'],
                'is_active' => (int)($data['status'] == 1),
                //'categories' => $postCategories,
                //'author_id' => $postAuthorId ,
                //'tags' => $postTags,
                'featured_img' => str_replace('public://', 'magefan_blog/', $data['featured_img']),
                //'featured_list_img' => trim((string)$data['list_thumbnail'], '/'),
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


    protected function parseContent($content)
    {
        $content = str_replace('<!--more-->', '<!-- pagebreak -->', $content);

        $content = preg_replace(
            '/src="\/sites\/default\/files\/inline-images\/(.*)"/Ui',
            'src="{{media url=\'wysiwyg/inline-images/$1\'}}"',
            $content
        );

        $content = preg_replace(
            '/src=\'\/sites\/default\/files\/inline-images\/(.*)\'/Ui',
            'src="{{media url=\'wysiwyg/inline-images/$1\'}}"',
            $content
        );

        return $content;
    }
}
