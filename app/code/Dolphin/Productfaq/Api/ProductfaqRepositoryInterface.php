<?php


namespace Dolphin\Productfaq\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Dolphin\Productfaq\Api\Data\ProductfaqInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ProductfaqRepositoryInterface
{
    /**
     * Save Productfaq
     *
     * @param ProductfaqInterface $productfaq
     * @return ProductfaqInterface
     * @throws LocalizedException
     */
    public function save(
        ProductfaqInterface $productfaq
    );

    /**
     * Retrieve Productfaq
     *
     * @param string $productfaqId
     * @return ProductfaqInterface
     * @throws LocalizedException
     */
    public function get($productfaqId);

    /**
     * Retrieve Productfaq matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return ProductfaqSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Productfaq
     *
     * @param ProductfaqInterface $productfaq
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        ProductfaqInterface $productfaq
    );

    /**
     * Delete Productfaq by ID
     *
     * @param string $productfaqId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($productfaqId);
}
