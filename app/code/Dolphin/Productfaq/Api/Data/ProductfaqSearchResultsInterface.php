<?php

namespace Dolphin\Productfaq\Api\Data;

interface ProductfaqSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Productfaq list.
     *
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface[]
     */
    public function getItems();

    /**
     * Set question list.
     *
     * @param \Dolphin\Productfaq\Api\Data\ProductfaqInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
