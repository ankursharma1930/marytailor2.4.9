<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogImport\Model\Import;

/**
 * Magefan Blog Helper
 */
class Hubspot extends Csv
{
    public function execute()
    {
        $rows = $this->getRows();
        $column = [];
        foreach ($rows as $number => $row) {
            if (!$number) {
                $column = array_flip($row);
                continue;
            }


            /* Import author */
            $data = [];
            if (isset($column['Author']) && !empty($row[$column['Author']]) && ($authorFullname = (string)$row[$column['Author']])) {
                $authorInfo = explode(' ', $authorFullname);
                $authorData['firstname'] = $authorInfo[0];
                $authorData['lastname'] = isset($authorInfo[1]) ? $authorInfo[1] : $authorInfo[0];

                $author = $this->_authorFactory->create();
                try {
                    /* Author saving */
                    $author->setData($authorData)->save();
                    $this->_importedAuthorsCount++;
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    echo $e->getMessage();
                    exit;
                }

                $data['author_id'] = $author->getId();
            }

            /* Import tags */
            if (isset($column['Tags']) && !empty($row[$column['Tags']])) {
                $tags = explode(',', (string)$row[$column['Tags']]);
            } else {
                $tags = [];
            }
            $postTags = [];

            foreach ($tags as $tagTitle) {
                $tagData['title'] = trim($tagTitle);
                if (!$tagData['title']) {
                    continue;
                }

                if (is_numeric($tagData['title'])) {
                    $tagData['title'] = 't' . $tagData['title'];
                }

                $tagData['store_ids'] = [0];

                try {
                    /* Initial saving */
                    if (!isset($existingTags[$tagData['title']])) {
                        $tag = $this->_tagFactory->create();
                        $tag->setData($tagData);

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
                        $postTags[] = $tag->getId();
                        $existingTags[$tag->getTitle()] = $tag;
                        unset($tag);
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->_skippedTags[] = $data['title'];
                    $this->_logger->debug('Blog Tag Import [' . $data['title'] . ']: '. $e->getMessage());
                }
            }

            $data['tags'] = $postTags;

            $data['is_active'] = ($row[$column['State']] == 'PUBLISHED');
            if (!$data['is_active'] = ($row[$column['State']] == 'PUBLISHED')) {
                continue;
            }

            $data['publish_time'] = $row[$column['Publish Date']];
            $data['title'] = $row[$column['Name']];

            $data['creation_time'] = $row[$column['Creation Date']];
            $data['update_time'] = $row[$column['Last Update']];

            $html = file_get_contents($row[$column['URL']]);

            $previousErrorState = libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML('<?xml encoding="UTF-8">' . '<body>' . $html . '</body>');
            libxml_use_internal_errors($previousErrorState);

            $content = '';
            $noContentInPage = false;
            foreach (['span10 widget-span widget-type-cell '] as $classname) {

                $finder = new \DomXPath($dom);
                $nodes = $finder->query("//*[contains(@class, '$classname')]");
                if (0 === count($nodes)) {
                    $noContentInPage = true;
                    break;
                }

                $_content = new \DOMDocument;
                foreach ($nodes as $child) {
                    $_content->appendChild($_content->importNode($child, true));
                    //break;
                }
                $content .= $_content->saveHTML();

            }

            if ($noContentInPage) {
                $data['content'] = $this->parseContent($row[$column['Post body']]);
            } else {
                $data['content'] = $this->parseContent($content);
            }

            $data['featured_img'] = $this->getFeaturedImg($html);
            if (!$data['featured_img']) {
                if (isset($column['Featured image URL']) && !empty($row[$column['Featured image URL']])) {
                    $data['featured_img'] = $this->getFeaturedImgBySrc($row[$column['Featured image URL']]);
                }
            }

            $identifier = explode(
                '/',
                trim($row[$column['URL']], '/')
            );
            $identifier = end($identifier);

            $data['identifier'] = $identifier;
            $data['identifier'] = str_replace('.html', '', $data['identifier']);

            $data['store_ids'] = [$this->getStoreId()];

            $data['meta_title'] = (isset($column['Post SEO title']) && !empty($row[$column['Post SEO title']])) ? $row[$column['Post SEO title']] : '';
            $data['meta_description'] = $row[$column['Meta description']];

            $post = $this->_postFactory->create();
            try {
                /* Post saving */
                $post->setData($data)->save();
                $this->_importedPostsCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedPosts[] = isset($data['title']) ? $data['title'] : '';
            }

            unset($post);
            unset($author);
        }
    }

    protected function getFeaturedImgBySrc($src)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $fileSystem = $objectManager->create(\Magento\Framework\Filesystem::class);
        $mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath() . '/magefan_blog';
        @mkdir($mediaPath, 0777, true);

        $imageName = explode('?', $src);
        $imageName = explode('/', $imageName[0]);
        $imageName = end($imageName);
        $imageName = str_replace(['%20', ' '], '-', $imageName);
        $imageName = urldecode($imageName);
        $imagePath = $mediaPath . '/' . $imageName;
        if (!file_exists($imagePath)) {

            if ($imageSource = @file_get_contents($src)) {
                file_put_contents(
                    $imagePath,
                    $imageSource
                );
            }
        } else {
            $imageSource = true;
        }

        if ($imageSource) {
            return 'magefan_blog/' . $imageName;
        } else {
            return false;
        }
    }

    protected function getFeaturedImg($content)
    {
        $p = strpos($content, 'post_featured_image');
        if ($p === false) {
            return null;
        }
        $s = ':url(';

        $p = strpos($content, $s, $p);
        $p2 = strpos($content, ')', $p);
        if ($p === false || $p2 === false) {
            return null;
        }

        $p = $p + strlen($s);

        $src = substr($content, $p, $p2 - $p);
        $src = trim($src, "'");

        if ($imageName = $this->getFeaturedImgBySrc($src)) {
            return $imageName;
        }
        return null;
    }


    protected function parseContent($content)
    {

        $content = str_replace('<!--more-->', '<!-- pagebreak -->', $content);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $fileSystem = $objectManager->create(\Magento\Framework\Filesystem::class);
        $mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath() . '/wysiwyg/blog';
        @mkdir($mediaPath, 0777, true);

        foreach (['/src="(.*)"/Ui', '/src=\'(.*)\'/Ui'] as $patern) {

            $matches = [];
            preg_match_all($patern, $content, $matches);


            if (!empty($matches[1])) {

                foreach ($matches[1] as $src) {
                    //$src = $matches[1];
                    $imageName = explode('?', $src);
                    $imageName = explode('/', $imageName[0]);
                    $imageName = end($imageName);
                    $imageName = str_replace(['%20', ' '], '-', $imageName);
                    $imageName = urldecode($imageName);
                    $imagePath = $mediaPath . '/' . $imageName;
                    if (!file_exists($imagePath)) {

                        if ($imageSource = @file_get_contents($src)) {
                            file_put_contents(
                                $imagePath,
                                $imageSource
                            );
                        }
                    } else {
                        $imageSource = true;
                    }

                    if ($imageSource) {
                        $content = str_replace($src, '{{media url=\'wysiwyg/blog/' . $imageName . '\'}}', $content);
                    } else {
                        $content = str_replace($src, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', $content);
                    }
                }
            }

        }

        $content = preg_replace(
            '/(srcset=".*")/Ui',
            's',
            $content
        );
        $content = preg_replace(
            '/(srcset=\'.*\')/Ui',
            's',
            $content
        );

        return $content;
    }


    /**
     * Prepare import data
     * @param  array $data
     * @return $this
     */
    public function prepareData($data)
    {
        return $this->setData((array)$data);
    }
}
