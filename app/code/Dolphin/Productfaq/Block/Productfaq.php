<?php
namespace Dolphin\Productfaq\Block;

use Dolphin\Productfaq\Helper\ProductFaqHelper;
use Dolphin\Productfaq\Model\ProductfaqFactory;
use Dolphin\Productfaq\Model\ProductsFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Productfaq extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ProductfaqFactory
     */
    private $productfaqFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var SessionFactory
     */
    protected $customerSessionFactory;

    /**
     * @var ProductFactory
     */
    protected $product;

    /**
     * @var ProductsFactory
     */
    protected $proModel;

    /**
     * @var ProductFaqHelper
     */
    protected $Productfaqhelper;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Data
     */
    protected $reffurl;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var ToolbarMemorizer
     */
    private $toolbarMemorizer;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var EncoderInterface
     */
    protected $urlEncode;

    /**
     * @var DecoderInterface
     */
    protected $urlDecode;

    /**
     * Constructor for the Product FAQ Controller.
     *
     * @param Registry $registry
     * @param Context $context
     * @param ProductfaqFactory $productfaqFactory
     * @param ResourceConnection $resource
     * @param ResultFactory $resultFactory
     * @param SessionManagerInterface $session
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param Data $reffurl
     * @param ProductsFactory $proModel
     * @param SessionFactory $customerSessionFactory
     * @param ProductFactory $product
     * @param Http $request
     * @param ProductFaqHelper $Productfaqhelper
     * @param DataPersistorInterface $dataPersistor
     * @param ToolbarMemorizer|null $toolbarMemorizer
     * @param EncoderInterface $urlEncode
     * @param DecoderInterface $urlDecode
     * @param array $data
     */
    public function __construct(
        Registry $registry,
        Context $context,
        ProductfaqFactory $productfaqFactory,
        ResourceConnection $resource,
        ResultFactory $resultFactory,
        SessionManagerInterface $session,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        Data $reffurl,
        ProductsFactory $proModel,
        SessionFactory $customerSessionFactory,
        ProductFactory $product,
        Http $request,
        ProductFaqHelper $Productfaqhelper,
        DataPersistorInterface $dataPersistor,
        ToolbarMemorizer $toolbarMemorizer,
        EncoderInterface $urlEncode,
        DecoderInterface $urlDecode,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->productfaqFactory = $productfaqFactory;
        $this->resource = $resource;
        $this->session = $session;
        $this->resultFactory = $resultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->product = $product;
        $this->proModel = $proModel;
        $this->request = $request;
        $this->reffurl = $reffurl;
        $this->Productfaqhelper = $Productfaqhelper;
        $this->dataPersistor = $dataPersistor;
        $this->toolbarMemorizer = $toolbarMemorizer;
        $this->urlEncode = $urlEncode;
        $this->urlDecode = $urlDecode;

        parent::__construct(
            $context,
            $data
        );
        $this->setTabTitle();
    }

    /**
     * Prepares layout for the product FAQ page.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->pageConfig->getTitle()->set(__('My Questions'));

        if ($this->getProductCount()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'dolphin.productfaq.pager'
            )->setAvailableLimit($this->getAvailableLimit())
                ->setShowPerPage(true)->setLimit(
                    $this->getLimit()
                )->setCollection(
                    $this->getProductCount()
                );
            $this->setChild('pager', $pager);
            $this->getProductCount()->load();
        }

        if ($this->getCustomerFaqQuery()) {
            $pagertab = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'dolphin.productfaq.pagertab'
            )->setAvailableLimit($this->getAvailableLimit())
                ->setShowPerPage(true)->setLimit(
                    $this->getLimit()
                )->setCollection(
                    $this->getCustomerFaqQuery()
                );
            $this->setChild('pagertab', $pagertab);
            $this->getCustomerFaqQuery()->load();
        }
        return $this;
    }

    /**
     * Get value for sorting option from session.
     *
     * @return mixed
     */
    public function getValueSortOption()
    {
        $this->session->start();
        return $this->session->getSortOption();
    }
    /**
     * Get pagination HTML for the product FAQ page.
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
    /**
     * Get pagination HTML for the customer FAQ tab.
     *
     * @return string
     */
    public function getPagerHtmlTab()
    {
        return $this->getChildHtml('pagertab');
    }
    /**
     * Get the current product from registry.
     *
     * @return mixed
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
    /**
     * Set the title for the product FAQ tab.
     *
     * Sets the title based on the product count.
     */
    public function setTabTitle()
    {
        $title = $this->getProductCount()
        ? __('Questions %1', '<span class="counter">' . $this->getProductCount()->getSize() . '</span>')
        : __('Questions');
        $this->setTitle($title);
    }

    /**
     * Get Url parameter
     *
     * @return string
     */
    public function getDirection()
    {
        return $this->getRequest()->getParam('dir');
    }

    /**
     * Get Form Url
     *
     * @param string $dir
     * @return string
     */
    public function getFormUrl($dir)
    {
        $url = $this->getRequest()->getUriString();
        //@codingStandardsIgnoreStart
        $parts = parse_url($url);
        //@codingStandardsIgnoreEnd
        if (isset($parts['query'])) {
            //@codingStandardsIgnoreStart
            parse_str($parts['query'], $query);
            //@codingStandardsIgnoreEnd
            $url = explode('?', $url)[0];
            $paramCount = 1;
            foreach ($query as $key => $val) {
                if ($paramCount == 1) {
                    $url .= '?';
                } else {
                    $url .= '&';
                }
                if ($key == 'dir' && $dir == 'asc') {
                    $url .= 'dir=asc';
                } elseif ($key == 'dir' && $dir == 'desc') {
                    $url .= 'dir=desc';
                } elseif ($val) {
                    $url .= $key . '=' . $val;
                    if ($dir == 'asc') {
                        $url .= '&dir=asc';
                    } else {
                        $url .= '&dir=desc';
                    }
                } else {
                    $url .= $key;
                    if ($dir == 'asc') {
                        $url .= '&dir=asc';
                    } else {
                        $url .= '&dir=desc';
                    }
                }
                $paramCount++;
            }
        } else {
            if ($dir == 'asc') {
                $url .= '?dir=asc';
            } else {
                $url .= '?dir=desc';
            }
        }

        return $url;
    }
    /**
     * Retrieve the product FAQ collection for the current product.
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function getProductCount()
    {
        $page = ($this->getRequest()->getParam('p')) ? $this->getRequest()->getParam('p') : 1;
        $limit = $this->getLimit();
        $customerSession = $this->isCustomerLoggedIn();
        $controller = $this->request->getControllerName();
        $currentstoreid = $this->getStoreId();
        $storeid = [$currentstoreid, 0];
        if ($controller == 'product') {
            $proid = $this->getCurrentProduct()->getId();
            $collection = $this->productfaqFactory->create()->getCollection()
                ->setOrder('group_faq', 'desc');
            $this->session->getSortOption();
            $collection = $this->productfaqFactory->create()->getCollection();
            if ($this->getDirection()) {
                $collection->setOrder('productfaq_id', $this->getDirection());
            } else {
                $collection->setOrder('productfaq_id', 'DESC');
            }

            $secondtable = $this->resource->getTableName('dolphin_productfaqgrid_rel');
            $collection->addFieldToFilter('answer_faq', ['neq' => ''])->addFieldToFilter('store_faq', $storeid)
                ->addFieldToFilter('status', ['eq' => 1]);
            if ($customerSession) {
                $customer = $this->getCustomer();
                $customer_id = $customer->getEntity_id();

                $collection->getSelect()->join(
                    ['pf' => $secondtable],
                    'pf.productfaq_id = main_table.productfaq_id',
                    ['product_id']
                );
                $collection->addFieldToFilter(['pf.product_id', 'main_table.group_faq'], [
                    ['eq' => $proid],
                    1,
                ]);
                $collection->addFieldToFilter('pf.product_id', ['eq' => $proid]);
                $collection->addFieldToFilter(['main_table.customer_id', 'main_table.visibility_faq'], [
                    ['eq' => $customer_id],
                    0,
                ]);
                $collection->setPageSize($limit);
                $collection->setCurPage($page);
                return $collection;
            } else {
                $collection->getSelect()->join(
                    ['pf' => $secondtable],
                    'pf.productfaq_id = main_table.productfaq_id',
                    ['product_id']
                );
                $collection->addFieldToFilter('pf.product_id', ['eq' => $proid]);
                $collection->addFieldToFilter('main_table.visibility_faq', 0);
                $collection->setPageSize($limit);
                $collection->setCurPage($page);
                return $collection;
            }
        }
    }
    /**
     * Retrieve the product FAQ collection for structured data (schema.org).
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function getFaqCollectionForSchema()
    {
        $customerSession = $this->isCustomerLoggedIn();
        $controller = $this->request->getControllerName();
        $currentstoreid = $this->getStoreId();
        $storeid = [$currentstoreid, 0];
        if ($controller == 'product') {
            $proid = $this->getCurrentProduct()->getId();
            $collection = $this->productfaqFactory->create()->getCollection()
                ->setOrder('group_faq', 'desc');
            $this->session->getSortOption();
            $collection = $this->productfaqFactory->create()->getCollection();
            if ($this->session->getSortOption() == 'oldest') {
                $collection->setOrder('productfaq_id', 'ASC');
            } else {
                $collection->setOrder('productfaq_id', 'DESC');
            }
            $secondtable = $this->resource->getTableName('dolphin_productfaqgrid_rel');
            $collection->addFieldToFilter('answer_faq', ['neq' => ''])->addFieldToFilter('store_faq', $storeid)
                ->addFieldToFilter('status', ['eq' => 1]);
            if ($customerSession) {
                $customer = $this->getCustomer();
                $customer_id = $customer->getEntity_id();

                $collection->getSelect()->join(
                    ['pf' => $secondtable],
                    'pf.productfaq_id = main_table.productfaq_id',
                    ['product_id']
                );

                $collection->addFieldToFilter(['pf.product_id', 'main_table.group_faq'], [
                    ['eq' => $proid],
                    1,
                ]);
                $collection->addFieldToFilter('pf.product_id', ['eq' => $proid]);
                $collection->addFieldToFilter(['main_table.customer_id', 'main_table.visibility_faq'], [
                    ['eq' => $customer_id],
                    0,
                ]);

                return $collection;
            } else {
                $collection->getSelect()->join(
                    ['pf' => $secondtable],
                    'pf.productfaq_id = main_table.productfaq_id',
                    ['product_id']
                );
                $collection->addFieldToFilter('pf.product_id', ['eq' => $proid]);
                $collection->addFieldToFilter('main_table.visibility_faq', 0);

                return $collection;
            }
        }
    }
    /**
     * Retrieve the available limit options for questions per page.
     *
     * @return array
     */
    public function getAvailableLimit()
    {
        $perPageConfigKey = 'productfaq/productfaq/question_per_page_values';
        $perPageValues = (string) $this->scopeConfig->getValue(
            $perPageConfigKey,
            ScopeInterface::SCOPE_STORE
        );
        $perpage = $this->getQuestionsPerPage();
        $perPageValues = explode(',', $perPageValues);
        array_push($perPageValues, $perpage);
        $perPageValues = array_combine($perPageValues, $perPageValues);
        return $perPageValues;
    }
    /**
     * Retrieve the limit for questions per page.
     *
     * @return int
     */
    public function getLimit()
    {
        $limit = $this->getRequest()->getParam('limit');
        if ($limit) {
            return $limit;
        }
        $limits = $this->getAvailableLimit();
        $defaultLimit = $this->getQuestionsPerPage();
        if (!$defaultLimit || !isset($limits[$defaultLimit])) {
            $keys = array_keys($limits);
            $defaultLimit = $keys[0];
        }
        $limit = $this->toolbarMemorizer->getLimit();
        if (!$limit || !isset($limits[$limit])) {
            $limit = $defaultLimit;
        }

        $this->setData('limit', $limit);
        return $limit;
    }
    /**
     * Check if a customer is logged in.
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSessionFactory->create()->isLoggedIn();
    }
    /**
     * Retrieve the logged-in customer.
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        $customerSession = $this->customerSessionFactory->create();
        $customer = $customerSession->getCustomer();
        return $customer;
    }

    /**
     * Get Encode Url
     *
     * @param array $url
     */
    public function getEncodeUrl($url)
    {

        return $this->urlEncode->encode($url);
    }
    /**
     * Get Decode Url
     *
     * @param array $url
     */
    public function getDecodeUrl($url)
    {
        return $this->urlDecode->decode($url);
    }

    /**
     * Retrieve the FAQ query for a specific product FAQ ID.
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function getFaqQuery()
    {
        $productFaqId = $this->getDecodeUrl($this->getRequest()->getParam('key2'));
        $productId = $this->getDecodeUrl($this->getRequest()->getParam('key1'));

        $customer = $this->getCustomer();
        $customer_id = $customer->getEntity_id();
        $customeremail = $customer->getEmail();
        $collection = $this->proModel->create()->getCollection();
        $secondtable = $this->resource->getTableName('dolphin_productfaq_productfaq');

        $collection->getSelect()->join(
            ['pf' => $secondtable],
            'pf.productfaq_id = main_table.productfaq_id',
        );

        $collection->addFieldToFilter(['pf.customer_id', 'pf.email_faq'], [
            ['eq' => $customer_id],
            ['eq' => $customeremail],
        ]);

        $collection->addFieldToFilter('main_table.product_id', ['eq' => $productId]);
        $collection->addFieldToFilter('main_table.productfaq_id', ['eq' => $productFaqId]);

        return $collection;
    }
    /**
     * Retrieve the FAQ query for a customer.
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function getCustomerFaqQuery()
    {
        $controller = $this->request->getControllerName();
        if ($controller == 'customer') {
            $currentstoreid = $this->getStoreId();
            $storeid = [$currentstoreid, 0];
            $page = ($this->getRequest()->getParam('p')) ? $this->getRequest()->getParam('p') : 1;
            $limit = (int) $this->getLimit();
            $customer = $this->getCustomer();
            $customer_id = $customer->getEntity_id();
            $customeremail = $customer->getEmail();
            $collection = $this->proModel->create()->getCollection();
            $secondtable = $this->resource->getTableName('dolphin_productfaq_productfaq');

            $collection->getSelect()->join(
                ['pf' => $secondtable],
                'pf.productfaq_id = main_table.productfaq_id',
            );

            $collection
                ->addFieldToFilter('answer_faq', ['neq' => ''])
                ->addFieldToFilter('store_faq', $storeid);

            $collection->addFieldToFilter(['pf.customer_id', 'pf.email_faq'], [
                ['eq' => $customer_id],
                ['eq' => $customeremail],
            ]);

            $collection->setPageSize($limit);
            $collection->setCurPage($page);

            return $collection;
        }
    }
    /**
     * Retrieve the product FAQ helper instance.
     *
     * @return ProductFaqHelper
     */
    public function getFaqHelper()
    {
        return $this->Productfaqhelper;
    }
    /**
     * Retrieve the product by ID.
     *
     * @param int $id
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct($id)
    {
        return $this->product->create()->load($id);
    }
    /**
     * Retrieve the store ID.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
    /**
     * Set the store scope.
     *
     * @return string
     */
    public function setStoreScope()
    {
        return ScopeInterface::SCOPE_STORE;
    }
    /**
     * Retrieve the number of questions per page from configuration.
     *
     * @return int
     */
    public function getQuestionsPerPage()
    {
        $config = 'productfaq/productfaq/questions_per_page';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieve data after error.
     *
     * @return mixed
     */
    public function getDataAfterError()
    {
        return $this->dataPersistor->get('dolphin_productfaq_productfaq');
    }
    /**
     * Retrieve the referral URL.
     *
     * @return string
     */
    public function getRefferalUrl()
    {
        $refererurl = $this->reffurl->getEncodedUrl($this->getUrl($this->getCurrentProduct()->getProductUrl()));
        $redirect = $this->getUrl('customer/account/login', ['referer' => $refererurl]);
        return $redirect;
    }
}
