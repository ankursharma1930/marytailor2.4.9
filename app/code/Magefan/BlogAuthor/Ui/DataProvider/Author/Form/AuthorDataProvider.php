<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogAuthor\Ui\DataProvider\Author\Form;

use Magefan\Blog\Api\AuthorCollectionInterfaceFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class DataProvider
 */
class AuthorDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magefan\Blog\Model\ResourceModel\Author\Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * AuthorDataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param AuthorCollectionInterfaceFactory $authorCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        AuthorCollectionInterfaceFactory $authorCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $authorCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
    }

    /**
     * Prepares Meta
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var $author \Magefan\Blog\Model\Author */
        foreach ($items as $author) {
            $author = $author->load($author->getId()); //temporary fix
            $data = $author->getData();
            /* Prepare Featured Image */
            $map = [
                'featured_img' => 'getFeaturedImage'
            ];
            foreach ($map as $key => $method) {
                if (isset($data[$key])) {
                    $name = $data[$key];
                    unset($data[$key]);
                    $data[$key][0] = [
                        'name' => $name,
                        'url' => $author->$method(),
                    ];
                }
            }




            $this->loadedData[$author->getId()] = $data;
        }

        $data = $this->dataPersistor->get('blogauthor_author_form_data');
        if (!empty($data)) {
            $author = $this->collection->getNewEmptyItem();
            $author->setData($data);
            $this->loadedData[$author->getId()] = $author->getData();
            $this->dataPersistor->clear('blogauthor_author_form_data');
        }

        return $this->loadedData;
    }
}
