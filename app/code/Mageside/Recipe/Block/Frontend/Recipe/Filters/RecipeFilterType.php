<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe\Filters;

use Magento\Store\Model\StoreManagerInterface;

class RecipeFilterType extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory
     */
    protected $filterFactory;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options\CollectionFactory
     */
    protected $optionsCollectionFactory;

    /**
     * Store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * RecipeFilterType constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory $filterFactory
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options\CollectionFactory $optionsCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory $filterFactory,
        \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options\CollectionFactory $optionsCollectionFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->filterFactory = $filterFactory;
        $this->optionsCollectionFactory = $optionsCollectionFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * @return \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFilterType()
    {
        $collection = $this->filterFactory->create();
        $collection = $collection->joinOptionData();

        $storeId = $this->storeManager->getStore()->getId();
        foreach ($collection->getItems() as $item) {
            $stores = $item->getStoreId() ?? ['0'];
            if (in_array($storeId, $stores) || in_array('0', $stores)) {
                continue;
            } else {
                $collection->removeItemByKey($item->getId());
            }
        }

        return $collection;
    }

    /**
     * @param $id
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFilterOption($id)
    {
        $options = $this->optionsCollectionFactory->create();
        $options = $options->joinOptionData($id);
        $this->setData('options', $options->getData('label'));

        return $this;
    }
}
