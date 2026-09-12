<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogImport\Model\Import;

/**
 * Joomla import model
 */
class Joomla extends \Magefan\Blog\Model\Import\AbstractImport
{
    protected $_requiredFields = ['dbname', 'uname', 'pwd', 'dbhost', 'prefix'];


    public function execute()
    {
        $con = $this->_connect = mysqli_connect(
            $this->getData('dbhost'),
            $this->getData('uname'),
            $this->getData('pwd'),
            $this->getData('dbname')
        );

        if (mysqli_connect_errno()) {
            throw new \Exception("Failed connect to joomla database", 1);
        }

        mysqli_set_charset($con, "utf8");

        $_pref = mysqli_real_escape_string($con, $this->getData('prefix'));

        $categories = [];
        $oldCategories = [];
        $adapter = $this->getDbAdapter();

        /* Import categories */
        $sql = 'SELECT
                    id as old_id,
                    description as content,
                    parent_id as parent_id,
                    lft as  position,
                    published as is_active,
                    metadesc as meta_description,
                    metakey as meta_keywords,
                    title as title,
                    alias as identifier
                FROM '.$_pref.'categories
                WHERE extension = "com_content"';


        $result = $adapter->query($sql)->execute();
        foreach ($result as $data) {
            /* Prepare category data */
            foreach (['title', 'identifier','meta_description','meta_keywords','content'] as $key) {
                $data[$key] = mb_convert_encoding($data[$key], "UTF-8", "ISO-8859-1");
            }

            $data['content'] = $this->parseContent($data['content']);

            $data['store_ids'] = [$this->getStoreId()];
            $data['is_active'] = (int)($data['is_active'] == 1);
            $data['identifier'] = trim(strtolower($data['identifier']));
            $data['include_in_menu'] = 1;
            if (strlen($data['identifier']) == 1) {
                $data['identifier'] .= $data['identifier'];
            }


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
        /* end*/

        foreach ($categories as $ct) {
            /* Final saving */
            $ct->save();
        }


        /* Import posts */
        $sql = "SELECT
        metakey as meta_keywords,
        metadesc as meta_description,
        created as creation_time,
        modified as update_time,
        publish_up as publish_time,
        catid as category_id,
        title as post_title,
        access as is_active,
        alias as identifier,
        introtext as short_content,
        `fulltext` as post_content
        FROM ".$_pref."content";
        $result = $adapter->query($sql)->execute();
        foreach ($result as $data) {

            if (isset($oldCategories[$data['category_id']])) {
                $postCategories = [
                    $oldCategories[$data['category_id']]->getId()
                ];
            } else {
                $postCategories = [];
            }

            $data['featured_img'] = '';


            /* Prepare post data */
            foreach (['post_title', 'meta_keywords', 'meta_description', 'identifier', 'post_content', 'identifier'] as $key) {
                $data[$key] = mb_convert_encoding($data[$key], "UTF-8", "ISO-8859-1");
            }

            $creationTime = strtotime($data['creation_time']);

            $content = $this->parseContent($data['post_content']);
            $shortContent = $this->parseContent($data['short_content']);

            $data = [
                'store_ids' => [$this->getStoreId()],
                'title' => $data['post_title'],
                'meta_keywords' => $data['meta_keywords'],
                'meta_description' => $data['meta_description'],
                'identifier' => $data['identifier'],
                'content_heading' => '',
                'content' => $content,
                'short_content' => $shortContent,
                'creation_time' => $creationTime,
                'update_time' => strtotime($data['update_time']),
                'publish_time' => $creationTime,
                'is_active' => (int)($data['is_active'] == 1),
                'categories' => $postCategories,
            ];
            $data['identifier'] = trim(strtolower($data['identifier']));
            if (strlen($data['identifier']) == 1) {
                $data['identifier'] .= $data['identifier'];
            }
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

        mysqli_close($con);
    }


    protected function parseContent($content)
    {
        $content = str_replace('<!--more-->', '<!-- pagebreak -->', $content);

        $content = preg_replace(
            '/src="images\/(.*)"/Ui',
            'src="{{media url=\'wysiwyg/images/$1\'}}"',
            $content
        );

        $content = preg_replace(
            '/src=\'images\/(.*)\'/Ui',
            'src="{{media url=\'wysiwyg/images/$1\'}}"',
            $content
        );

        $content = preg_replace(
            '/"((shop)(.*)|(\s|"|\')|(\/[\d\w_\-\.]*))\/(.*)(\s|"|\')/Ui',
            '$4"{{store url=$6$8}}"$9',
            $content
        );

        //$content = str_replace('<h1 class="bottomline">' . $data['post_title'] . '</h1>', '', $content);

        return $content;
    }
}
