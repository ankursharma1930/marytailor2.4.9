<?php
/**
 * @package     Plumrocket_Bestsellers
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Bestsellers\Model\Report\Fallback;

use Magento\Framework\Exception\NoSuchEntityException;
use Plumrocket\Bestsellers\Model\FallbackStrategyInterface;
use Psr\Log\LoggerInterface;

/**
 * Class RandomStrategy
 *
 * Generate random list instead of bestsellers
 */
class RandomStrategy implements FallbackStrategyInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    private $productVisibility;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * RandomStrategy constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Model\Product\Visibility                      $productVisibility
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface               $categoryRepository
     * @param \Psr\Log\LoggerInterface                                       $logger
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->productVisibility = $productVisibility;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function generateIdList(array $productIds, int $limit, int $storeId, int $categoryId = 0) : array
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->collectionFactory->create();
        $productCollection->setVisibility($this->productVisibility->getVisibleInCatalogIds())
                          ->addFieldToSelect('entity_id')
                          ->addStoreFilter($storeId)->setPageSize($limit);

        $productCollection->getSelect()->orderRand();

        if ($categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
                $productCollection->addCategoryFilter($category);
            } catch (NoSuchEntityException $noSuchEntityException) {
                $this->logger->debug($noSuchEntityException);
            }
        }

        return $productCollection->getColumnValues('entity_id');
    }
}
