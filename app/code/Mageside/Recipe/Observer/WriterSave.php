<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Observer;

use Mageside\Recipe\Model\WriterFactory;
use Mageside\Recipe\Model\FileUploader;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Event\ManagerInterface;

/**
 * Class WriterSave
 * @package Mageside\Recipe\Model\Plugin
 */
class WriterSave implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var array
     */
    protected $availableImages = ['avatar', 'writer_image'];

    /**
     * @var WriterFactory
     */
    protected $writerModelFactory;

    /**
     * @var FileUploader
     */
    protected $fileUploader;

    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $filter;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * WriterSave constructor.
     * @param FileUploader $fileUploader
     * @param WriterFactory $writerModel
     * @param FilterManager $filter
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        FileUploader $fileUploader,
        WriterFactory $writerModel,
        FilterManager $filter,
        ManagerInterface $eventManager
    ) {
        $this->fileUploader = $fileUploader;
        $this->writerModelFactory = $writerModel;
        $this->filter = $filter;
        $this->eventManager = $eventManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $observer->getEvent()->getCustomer();
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $observer->getEvent()->getRequest();

        if (!$customerId = $customer->getId()) {
            return $this;
        }

        $data = $request->getPost('customer');
        if (empty($data['writer'])) {
            return $this;
        }

        $dataWriter = $data['writer'];
        $dataWriter['customer_id'] = $customerId;

        foreach ($this->availableImages as $imageName) {
            if (!empty($dataWriter[$imageName])) {
                $image = $dataWriter[$imageName][0]['name'];
                $this->fileUploader->moveFileFromTmp($image);
                unset($dataWriter[$imageName]);
                $dataWriter[$imageName] = $image;
            } else {
                $dataWriter[$imageName] = '';
            }
        }

        $urlKey = isset($dataWriter['writer_url_key']) ? trim($dataWriter['writer_url_key']) : false;
        if (!empty($urlKey)) {
            $urlKey = $this->filter->translitUrl($urlKey);
        } else {
            $urlKey = $this->filter->translitUrl($customer->getFirstname() . '-' . $customer->getLastname());
        }

        /** @var \Mageside\Recipe\Model\Writer $model */
        $model = $this->writerModelFactory->create();
        $model->load($customerId, 'customer_id');
        $model->addData($dataWriter);

        $count = 0;
        if ($model->isObjectNew() || $model->dataHasChangedFor('writer_url_key')) {
            $urlKey = $this->checkUrlRewrites($count, $urlKey, $model->getResource(), $model->getResource()->getConnection(), $model->getId());
        }

        if ($count >= 10) {
            throw new \Exception('Url key already exist is some url rewrite!!! Please change request path - ' . $urlKey . ' !!!');

        }

        $model->setWriterUrlKey($urlKey);
        $model->save();

        // After save
        $this->eventManager->dispatch(
            'recipe_writer_save_after',
            ['object' => $model]
        );

        return $this;
    }

    /**
     * Checking unique url_key in url_rewrite table.
     * @param $count
     * @param $urlKey
     * @param $resource
     * @param $connection
     * @param null $id
     * @return string
     */
    protected function checkUrlRewrites(&$count, $urlKey, $resource, $connection, $id)
    {
        $newUrlKey = $urlKey;
        if ($count >= 1) {
            $newUrlKey = $urlKey . '-' . $count;
        }
        if ($count >= 10) {
            return $newUrlKey;
        }
        $count++;

        $select = $connection->select()->from(
            $resource->getTable('url_rewrite'),
            ['request_path']
        )->where('request_path = ?', $newUrlKey);
        if ($id) {
            $select->where('entity_id != ?', $id);
        }
        $result = $connection->fetchCol($select);
        $urlRewriteCheck = reset($result);

        $select = $connection->select()->from(
            $resource->getTable('ms_recipe_writer'),
            ['writer_url_key']
        )->where('writer_url_key = ?', $newUrlKey);
        if ($id) {
            $select->where('id != ?', $id);
        }

        $result = $connection->fetchCol($select);
        $urlKeyCheck = reset($result);

        if ($urlKeyCheck || $urlRewriteCheck) {
            $newUrlKey = $this->checkUrlRewrites($count, $urlKey, $resource, $connection, $id);
        }

        return $newUrlKey;
    }

}
