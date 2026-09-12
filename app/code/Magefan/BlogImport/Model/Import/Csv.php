<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogImport\Model\Import;

/**
 * Magefan Blog Helper
 */
class Csv extends \Magefan\Blog\Model\Import\AbstractImport
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magefan\Blog\Model\PostFactory $postFactory
     * @param \Magefan\Blog\Model\CategoryFactory $categoryFactory
     * @param \Magefan\Blog\Model\TagFactory $tagFactory
     * @param \Magefan\Blog\Model\CommentFactory $commentFactory
     * @param \Magento\Backend\Model\Session $session
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magefan\Blog\Model\PostFactory $postFactory,
        \Magefan\Blog\Model\CategoryFactory $categoryFactory,
        \Magefan\Blog\Model\TagFactory $tagFactory,
        \Magefan\Blog\Model\CommentFactory $commentFactory,
        \Magento\Backend\Model\Session $session,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $postFactory,
            $categoryFactory,
            $tagFactory,
            $commentFactory,
            $storeManager,
            $resource,
            $resourceCollection,
            $data
        );

        $this->session = $session;
        $this->directoryList = $directoryList;
        $this->messageManager = $messageManager;
    }


    public function getRows($rowsCount = null)
    {
        $file = $this->session->getData('import_csv_file');
        if (!$file) {
            throw new \Exception("CSV is missing.", 1);
        }

        $file = $this->directoryList->getPath('media') . '/' . $file;
        if (!file_exists($file)) {
            throw new \Exception("CSV is file not longer exist.", 1);
        }

        $fileFormat = explode('.', $file);
        $fileFormat = end($fileFormat);
        return (stripos($fileFormat, 'csv') !== false)
            ? $this->getCsvRows($file, $rowsCount)
            : $this->getXMLRows($file, $rowsCount);
    }

    protected function getCsvRows($file, $rowsCount = null)
    {
        $delimeter = $this->getDelimeter($file);

        if (($handle = fopen($file, "r")) !== false) {
            $data = [];

            $i = 0;
            while (($row = fgetcsv($handle, 0, $delimeter)) !== false) {
                $data[] = $row;
                $i++;
                if ($rowsCount && $i >= $rowsCount) {
                    break;
                }
            }
            fclose($handle);
            return $data;
        } else {
            throw new \Exception("Cannot read csv file.", 1);
        }
    }

    protected function getXMLRows($file, $rowsCount = null)
    {
        if (($xml = simplexml_load_file($file)) !== false) {
            $data = [];
            $i = 0;
            $items = $xml->channel ? $xml->channel->item : $xml->item;
            foreach ($items as $row) {
                foreach ($row as $k => $v) {
                    try {
                        $row->$k = is_array($v) ? '' : (string)$v;
                    } catch (\Exception $e) {

                    }
                }

                $row = json_decode(json_encode($row), 1);
                if (!$i) {
                    $data[] = array_keys($row);
                }

                $data[] = $row;
                $i++;
                if ($rowsCount && $i >= $rowsCount) {
                    break;
                }
            }
            return $data;
        } else {
            throw new \Exception("Cannot read XML file.", 1);
        }
    }

    protected function getDelimeter($file)
    {
        $delimeters = [';', ',', '|'];
        foreach ($delimeters as $delimeter) {
            $handle = fopen($file, "r");
            if ($handle !== false) {
                $row = fgetcsv($handle, 0, $delimeter);
                if ($row !== false) {
                    if (count($row) > 2) {
                        fclose($handle);
                        return $delimeter;
                    }
                }
            }
        }

        fclose($handle);
        return ',';
    }

    public function execute()
    {
        $rows = $this->getRows();
        $column = $this->getData('column');
        foreach ($rows as $number => $row) {
            if (!$number) {
                continue;
            }

            $data = [];
            $i = 0;
            foreach ($row as $key => $value) {
                $value = is_string($value) ? trim($value) : '';
                if ('' !== $value) {
                    $field = isset($column[$i]) ? $column[$i] : '';
                    $data[$field] = $value;
                }
                $i++;
            }

            foreach (['categories', 'tags', 'store_ids'] as $fieldName) {
                if (!empty($data[$fieldName]) && is_string($data[$fieldName])) {
                    $data[$fieldName] = explode(',', $data[$fieldName]);
                }
            }
            if (!empty($data['related_posts'])) {
                $data['links']['post'] = json_decode($data['related_posts'], true);
                if (is_array($data['links']['post'])) {
                    $data['links']['post'] = array_flip($data['links']['post']);
                }
            }
            if (!empty($data['related_products'])) {
                $data['links']['product'] = json_decode($data['related_products'], true);
                if (is_array($data['links']['product'])) {
                    $data['links']['product'] = array_flip($data['links']['product']);
                }
            }

            if (!empty($data['identifier'])) {
                $identifierInfo = explode('/', $data['identifier']);
                $data['identifier'] = end($identifierInfo);
                $data['identifier'] = str_replace('.html', '', $data['identifier']);
            }

            if (empty($data['store_ids'])) {
                $data['store_ids'] = [$this->getStoreId()];
            }

            if (empty($data['is_active'])) {
                $data['is_active'] = 1;
            }

            if (!empty($data['featured_img'])
                && false === strpos($data['featured_img'], \Magefan\Blog\Model\Post::BASE_MEDIA_PATH . '/')
            ) {
                $data['featured_img'] = \Magefan\Blog\Model\Post::BASE_MEDIA_PATH . '/' . $data['featured_img'];
            }

            if (isset($data['update_time'])) {
                unset($data['update_time']);
            }

            if (!empty($data['media_gallery'])) {
                $data['media_gallery'] = str_replace(
                    [',','|', ' '],
                    [';',';',''],
                    $data['media_gallery']
                );

                if ($data['media_gallery']) {
                    $gallery = [];
                    foreach (explode(';', $data['media_gallery']) as $image) {
                        if ($image) {
                            $gallery[] = \Magefan\Blog\Model\Post::BASE_MEDIA_PATH . '/' . $image;
                        }
                    }
                    $data['media_gallery'] = implode(';', $gallery);
                }
            }

            /* Check categories */
            if (isset($data['categories']) && count($data['categories'])) {
                foreach ($data['categories'] as $key => $dataCategory) {

                    $categoryId = null;

                    $category = $this->_categoryFactory->create();
                    $category->load($dataCategory);
                    if ($category->getId()) {
                        $categoryId = $category->getId();
                    } else {
                        $category = $this->_categoryFactory->create();
                        $category->load($dataCategory, 'title');

                        if ($category->getId()) {
                            $categoryId = $category->getId();
                        } else {
                            $category->setData([
                                'title' => $dataCategory
                            ]);

                            try {
                                $category->save();
                                $categoryId = $category->getId();
                            } catch (\Exception  $e) {
                                /* Do nothing */
                            }
                        }
                    }
                    if ($categoryId) {
                        $data['categories'][$key] = $categoryId;
                    } else {
                        unset($data['categories'][$key]);
                    }
                }
            }

            /* Check tags */
            if (isset($data['tags']) && count($data['tags'])) {
                foreach ($data['tags'] as $key => $dataTag) {

                    $tagId = null;

                    $tag = $this->_tagFactory->create();
                    $tag->load($dataTag);
                    if ($tag->getId()) {
                        $tagId = $tag->getId();
                    } else {
                        $tag = $this->_tagFactory->create();
                        $tag->load($dataTag, 'title');

                        if ($tag->getId()) {
                            $tagId = $tag->getId();
                        } else {
                            $tag->setData([
                                'title' => $dataTag
                            ]);

                            try {
                                $tag->save();
                                $tagId = $tag->getId();
                            } catch (\Exception  $e) {
                                /* Do nothing */
                            }
                        }
                    }
                    if ($tagId) {
                        $data['tags'][$key] = $tagId;
                    } else {
                        unset($data['tags'][$key]);
                    }
                }
            }

            $post = $this->_postFactory->create();
            try {
                /* Post saving */
                if (!empty($data['existing_posts'])) {
                    $post->load($data['existing_posts']);
                    if ($post->getId()) {
                        $post->addData($data);
                    } else {
                        $this->messageManager->addWarningMessage(
                            "Unable to load post ID: " . $data['existing_posts']
                        );
                        continue;
                    }
                } else {
                    $post->setData($data);
                }

                /* Fix URL duplicate issue */
                if (!$post->getId() && $post->getData('identifier')) {
                    $number = 1;
                    $identifier = $post->getData('identifier');
                    while (true) {
                        $number++;
                        $id = $post->checkIdentifier($post->getData('identifier'), $post->getData('store_ids'));
                        /*if ($id && $id !== $post->getId()) {*/
                        if ($id) {
                            $post->setData('identifier', $identifier . '-' . $number);
                        } else {
                            break;
                        }
                    }
                }

                $post->save();
                $this->_importedPostsCount++;
            } catch (\Magento\Framework\Exception\LocalizedException  $e) {
                $this->_skippedPosts[] = !empty($data['title']) ? $data['title'] : '';
            }

            unset($post);
        }
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
