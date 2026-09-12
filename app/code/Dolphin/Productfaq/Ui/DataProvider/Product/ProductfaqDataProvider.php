<?php

namespace Dolphin\Productfaq\Ui\DataProvider\Product;

use Dolphin\Productfaq\Model\ProductfaqFactory;
use Dolphin\Productfaq\Model\ProductsFactory;
use Dolphin\Productfaq\Model\ResourceModel\Productfaq\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ProductfaqDataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ProductfaqFactory
     */
    protected $productfaqFactory;

    /**
     * @var ProductsFactory
     */
    protected $ProductsFactory;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * Constructor function to initialize dependencies.
     *
     * @param string             $name
     * @param string             $primaryFieldName
     * @param string             $requestFieldName
     * @param CollectionFactory  $collectionFactory
     * @param RequestInterface   $request
     * @param ProductsFactory    $ProductsFactory
     * @param productfaqFactory  $productfaqFactory
     * @param ResourceConnection $resource
     * @param array              $meta
     * @param array              $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        ProductsFactory $ProductsFactory,
        productfaqFactory $productfaqFactory,
        ResourceConnection $resource,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collectionFactory = $collectionFactory;
        $this->collection = $this->collectionFactory->create();
        $this->productfaqFactory = $productfaqFactory;
        $this->ProductsFactory = $ProductsFactory;
        $this->request = $request;
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $secondtable = $this->resource->getTableName('dolphin_productfaqgrid_rel');
        $this->getCollection()->getSelect()->joinLeft(
            ['pf' => $secondtable],
            'pf.productfaq_id = main_table.productfaq_id',
            ['product_id']
        );
        $this->getCollection()->addFieldToFilter(
            'product_id',
            $this->request->getParam('current_product_id', 0)
        );
        $this->getCollection()->getSelect()->group('main_table.productfaq_id');

        $arrItems = [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => [],
        ];

        foreach ($this->getCollection() as $item) {
            $arrItems['items'][] = $item->toArray([]);
        }

        return $arrItems;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        $field = $filter->getField();
        $filter->setField($field);
        parent::addFilter($filter);
    }
}
