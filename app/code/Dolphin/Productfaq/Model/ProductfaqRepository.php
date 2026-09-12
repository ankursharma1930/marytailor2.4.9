<?php

namespace Dolphin\Productfaq\Model;

use Dolphin\Productfaq\Api\Data\ProductfaqInterfaceFactory;
use Dolphin\Productfaq\Api\Data\ProductfaqSearchResultsInterfaceFactory;
use Dolphin\Productfaq\Api\ProductfaqRepositoryInterface;
use Dolphin\Productfaq\Model\ResourceModel\Productfaq as ResourceProductfaq;
use Dolphin\Productfaq\Model\ResourceModel\Productfaq\CollectionFactory as ProductfaqCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Dolphin\Productfaq\Api\Data\ProductfaqInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

class ProductfaqRepository implements ProductfaqRepositoryInterface
{
    /**
     * @var ProductfaqFactory
     */
    protected $productfaqFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SearchResultsFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var ExtensionAttributesJoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var Data\ProductfaqFactory
     */
    protected $dataProductfaqFactory;

    /**
     * @var CollectionFactory
     */
    protected $productfaqCollectionFactory;

    /**
     * @param ResourceProductfaq $resource
     * @param ProductfaqFactory $productfaqFactory
     * @param ProductfaqInterfaceFactory $dataProductfaqFactory
     * @param ProductfaqCollectionFactory $productfaqCollectionFactory
     * @param ProductfaqSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceProductfaq $resource,
        ProductfaqFactory $productfaqFactory,
        ProductfaqInterfaceFactory $dataProductfaqFactory,
        ProductfaqCollectionFactory $productfaqCollectionFactory,
        ProductfaqSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->productfaqFactory = $productfaqFactory;
        $this->productfaqCollectionFactory = $productfaqCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataProductfaqFactory = $dataProductfaqFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * @inheritdoc
     */
    public function save(
        ProductfaqInterface $productfaq
    ) {
        $productfaqData = $this->extensibleDataObjectConverter->toNestedArray(
            $productfaq,
            [],
            ProductfaqInterface::class
        );
        $productfaqModel = $this->productfaqFactory->create()->setData($productfaqData);
        try {
            $this->resource->save($productfaqModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the productfaq: %1',
                $exception->getMessage()
            ));
        }
        return $productfaqModel->getDataModel();
    }
    /**
     * @inheritdoc
     */
    public function get($productfaqId)
    {
        $productfaq = $this->productfaqFactory->create();
        $this->resource->load($productfaq, $productfaqId);
        if (!$productfaq->getId()) {
            throw new NoSuchEntityException(__('Productfaq with id "%1" does not exist.', $productfaqId));
        }
        return $productfaq->getDataModel();
    }
    /**
     * @inheritdoc
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $collection = $this->productfaqCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            ProductfaqInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }
    /**
     * @inheritdoc
     */
    public function delete(
        ProductfaqInterface $productfaq
    ) {
        try {
            $productfaqModel = $this->productfaqFactory->create();
            $this->resource->load($productfaqModel, $productfaq->getProductfaqId());
            $this->resource->delete($productfaqModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Productfaq: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }
    /**
     * @inheritdoc
     */
    public function deleteById($productfaqId)
    {
        return $this->delete($this->get($productfaqId));
    }
}
