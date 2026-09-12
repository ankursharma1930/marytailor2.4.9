<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\Recipe;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options\CollectionFactory;

class Filter extends AbstractModel implements IdentityInterface
{
    /**
     * Filter cache tag
     */
    const CACHE_TAG = 'recipe_f';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     * @var string
     */
    protected $_eventPrefix = 'recipe_filter';

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options\CollectionFactory
     */
    protected $optionsCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * Filter constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options\CollectionFactory $optionsCollectionFactory
     * @param RequestInterface $request
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $optionsCollectionFactory,
        RequestInterface $request,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->optionsCollectionFactory = $optionsCollectionFactory;
        $this->_request = $request;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(\Mageside\Recipe\Model\ResourceModel\Recipe\Filter::class);
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        $filterId = $this->getId();

        if ($this->hasData('options')) {
            return $this->getData('options');
        }

        $this->setData('options', $this->loadOptionsByFilterId($filterId)->getData());

        return $this->getData('options');
    }

    /**
     * @param $filterId
     * @return \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\Options\Collection
     */
    public function loadOptionsByFilterId($filterId)
    {
        $options = $this->optionsCollectionFactory->create();
        $options->addFieldToFilter('filter_id', $filterId);

        return $options->load();
    }

    /**
     * Get identities
     * @return array
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG, self::CACHE_TAG . '_' . $this->getId()];
    }
}
