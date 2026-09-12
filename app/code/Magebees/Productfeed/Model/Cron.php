<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\ImportExport\Model\Export\Adapter\Csv;

class Cron extends \Magento\Framework\Model\AbstractModel
{
    protected $productRepository;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $csv;
    
    public function export()
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $$om->get('Psr\Log\LoggerInterface')->critical("This is testing");
        
        $items = $this->getProducts();
        $this->writeToFile($items);
    }
    public function getProducts()
    {
        $filters = [];
        $now = new \DateTime();
        $interval = new \DateInterval('P1W');
        $lastWeek = $now->sub($interval);
         
        $filters[] = $this->filterBuilder
            ->setField('created_at')
            ->setConditionType('gt')
            ->setValue($lastWeek->format('Y-m-d H:i:s'))
            ->create();
         
        $this->searchCriteriaBuilder->addFilter($filters);
         
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->productRepository->getList($searchCriteria);
        return $searchResults->getItems();
    }
    protected function writeToFile($items)
    {
        if (count($items) > 0) {
            $this->csv->setHeaderCols(['id', 'created_at', 'sku']);
            foreach ($items as $item) {
                $this->csv->writeRow(['id'=>$item->getId(), 'created_at' => $item->getCreatedAt(), 'sku' => $item->getSku()]);
            }
        }
    }
}
