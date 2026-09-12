<?php

/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Model\Config\Source;

/**
 * Class FbEnableAutopublishPost
 */
class FbEnableAutopublishPost implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magefan\Blog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * FbEnableAutopublishPost constructor.
     * @param \Magefan\Blog\Model\ResourceModel\Category\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magefan\Blog\Model\ResourceModel\Category\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function getGroupOptions()
    {
        return  [
            ['value' => 0, 'label' => __('No')],
            ['value' => 1, 'label' => __('Use config')]
        ];
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {

        $options = [];
        foreach ($this->getGroupOptions() as $item) {
            $options[] = [
                'value' => $item['value'],
                'label' => $item['label'],
            ];
        }
        return $options;
    }
}
