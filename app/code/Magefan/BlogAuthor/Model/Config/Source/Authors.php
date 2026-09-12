<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\BlogAuthor\Model\Config\Source;

/**
 * Used in edit post form
 *
 */
class Authors implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magefan\BlogAuthor\Model\ResourceModel\Author\CollectionFactory
     */
    protected $_authorCollectionFactory;

    /**
     * @var array
     */
    protected $_options;

    /**
     * @var array
     */
    protected $_childs;

    /**
     * Initialize dependencies.
     *
     * @param \Magefan\BlogAuthor\Model\ResourceModel\Author\CollectionFactory $authorCollectionFactory
     * @param void
     */
    public function __construct(
        \Magefan\BlogAuthor\Model\ResourceModel\Author\CollectionFactory $authorCollectionFactory
    ) {
        $this->_authorCollectionFactory = $authorCollectionFactory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->_options === null) {
            $this->_options = $this->_getOptions();
        }
        return $this->_options;
    }

    /**
     * @param int $itemId
     * @return array
     */
    protected function _getOptions($itemId = 0)
    {
        $childs =  $this->_getCollection();
        $options = [];

        foreach ($childs->getItems() as $item) {
            $data = [
                'label' => $item->getFirstname() . ' ' . $item->getLastname(),
                'value' => $item->getId(),
            ];
            $options[] = $data;
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function _getCollection()
    {
        if ($this->_childs === null) {
            $this->_childs = $this->_authorCollectionFactory->create();
        }
        return $this->_childs;
    }
}
