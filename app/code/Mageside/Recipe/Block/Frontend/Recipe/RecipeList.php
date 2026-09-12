<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe;

class RecipeList extends \Mageside\Recipe\Block\Frontend\AbstractBlock
{
    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Collection
     */
    protected $recipeCollectionFactory;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory
     */
    protected $filterCollectionFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Collection
     */
    protected $collection;

    /**
     * RecipeList constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     * @param \Mageside\Recipe\Helper\Config $helper
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\CollectionFactory $recipeFactory
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory $filterCollectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Mageside\Recipe\Model\FileUploader $fileUploader,
        \Mageside\Recipe\Helper\Config $helper,
        \Mageside\Recipe\Model\ResourceModel\Recipe\CollectionFactory $recipeFactory,
        \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory $filterCollectionFactory,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->recipeCollectionFactory = $recipeFactory;
        $this->filterCollectionFactory = $filterCollectionFactory;
        $this->coreRegistry = $coreRegistry;

        parent::__construct($context, $fileUploader, $helper);
    }

    /**
     * @return mixed
     */
    private function getProductId()
    {
        if ($product = $this->coreRegistry->registry('current_product')) {
            return $product->getEntityId();
        } elseif ($productId = $this->_request->getParam('productId')) {
            return $productId;
        }

        return false;
    }

    /**
     * @return int
     */
    private function getCurrentPage()
    {
        return (int) $this->_request->getParam('page', 1);
    }

    /**
     * @return \Mageside\Recipe\Model\ResourceModel\Recipe\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAvailableRecipeCollection()
    {
        if (!$this->collection) {
            /** @var \Mageside\Recipe\Model\ResourceModel\Recipe\Collection $recipes */
            $recipes = $this->recipeCollectionFactory->create()->joinRecipeData();
            $page = $this->getCurrentPage();
            $filters = $this->getSelectedFilters();

            if ($productId = $this->getProductId()) {
                $recipes->applyFilterByProductId($productId)
                    ->setPageSize($this->helper->getRecipesPerProductPage())
                    ->setCurPage($page ? $page : 1);

                if (!empty($filters)) {
                    $recipes->applySelectedFilters($filters);
                }
            } else {
                if (!empty($filters)) {
                    $recipes->applySelectedFilters($filters);
                }

                if ($writer = $this->getWriter()) {
                    $recipes->addFieldToFilter('customer_id', $writer->getCustomerId());
                }

                if ($search = $this->getSearchParam()) {
                    $recipes->applySearchKeywordFilter($search);
                }

                $recipes
                    ->addStoreFilter()
                    ->addIsEnableFilter()
                    ->addOrder('sort_order', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
                    ->addOrder('recipe_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                    ->setPageSize($this->helper->getRecipesPerPage())
                    ->setCurPage($page ? $page : 1);
            }

            $this->collection = $recipes;
        }

        return $this->collection;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCollectionSize()
    {
        $collection = $this->getAvailableRecipeCollection();
        return $collection->getSize();
    }

    /**
     * @return array
     */
    private function getSelectedFilters()
    {
        $filters = [];
        $params = $this->_request->getParams();
        if (!empty($params)) {
            $filterCollection = $this->filterCollectionFactory->create();
            foreach ($filterCollection->getItems() as $filter) {
                if (isset($params[$filter->getCode()])) {
                    $filters[$filter->getCode()] = $params[$filter->getCode()];
                }
            }
        }

        return $filters;
    }

    /**
     * @return mixed
     */
    private function getSearchParam()
    {
        return trim($this->_request->getParam('search', ''));
    }

    /**
     * @return mixed
     */
    private function getWriter()
    {
        return $this->coreRegistry->registry('writer');
    }

    /**
     * @return bool|string
     */
    private function getWriterUrlKey()
    {
        $writerUrlKey = false;
        if ($writer = $this->getWriter()) {
            $writerUrlKey = $writer->getWriterUrlKey();
        }

        return $writerUrlKey;
    }

    /**
     * @param $thumbnail
     * @return string
     */
    public function getRecipeThumbnail($thumbnail)
    {
        return $this->getImageUrl() . DIRECTORY_SEPARATOR . $thumbnail;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCountPage()
    {
        $countPage = 0;
        $size = $this->getCollectionSize();
        $perPage = $this->helper->getRecipesPerPage();

        if ($size > 0 && $perPage > 0) {
            $countPage = ceil($size / $perPage);
        }

        return (int) $countPage;
    }

    /**
     * @return bool|false|string
     */
    public function getJsonParams()
    {
        $params = [
            'writer'    => $this->getWriterUrlKey() ?: '',
            'productId' => $this->getProductId() ?: '',
            'filters'   => $this->getSelectedFilters(),
            'search'    => $this->getSearchParam(),
            'page'      => 1
        ];

        return json_encode($params);
    }

    /**
     * @return bool
     */
    public function isAjaxEnabled()
    {
        return $this->helper->isAjaxEnabled();
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->_urlBuilder->getUrl('recipe/recipe/listView');
    }

    /**
     * @return array
     */
    public function getHideParams()
    {
        $params = [];
        if ($this->getWriter()) {
            $params = ['writer'];
        } elseif ($this->getProductId()) {
            $params = ['productId'];
        }

        return json_encode($params);
    }
}
