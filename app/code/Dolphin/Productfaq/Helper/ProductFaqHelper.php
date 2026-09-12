<?php

namespace Dolphin\Productfaq\Helper;

use Dolphin\Productfaq\Model\ProductfaqFactory;
use Dolphin\Productfaq\Model\ProductsFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class ProductFaqHelper extends AbstractHelper
{
    /**
     * @var ProductsFactory
     */
    protected $proModel;

    /**
     * @var ProductfaqFactory
     */
    private $productfaqFactory;

    /**
     * @var FilterProvider
     */
    protected $filterProvider;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor for initializing dependencies of the class.
     *
     * @param Registry               $registry
     * @param Context                $context
     * @param ProductfaqFactory      $productfaqFactory
     * @param ResourceConnection     $resource
     * @param ScopeConfigInterface   $scopeConfig
     * @param ProductsFactory        $proModel
     * @param Http                   $request
     * @param FilterProvider         $filterProvider
     */
    public function __construct(
        Registry $registry,
        Context $context,
        ProductfaqFactory $productfaqFactory,
        ResourceConnection $resource,
        ScopeConfigInterface $scopeConfig,
        ProductsFactory $proModel,
        Http $request,
        FilterProvider $filterProvider
    ) {
        $this->registry = $registry;
        $this->proModel = $proModel;
        $this->productfaqFactory = $productfaqFactory;
        $this->resource = $resource;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->filterProvider = $filterProvider;
    }
    /**
     * Retrieves the FAQ collection based on the provided product FAQ ID.
     *
     * @return AbstractCollection
     */
    public function getFaqQuery()
    {
        $productFaqId = $this->request->getParam('id');
        $prodata = $this->proModel->create()->load($productFaqId);
        $collection = $this->productfaqFactory->create()->getCollection();
        $secondtable = $this->resource->getTableName('dolphin_productfaqgrid_rel');

        $collection->getSelect()->join(
            ['pf' => $secondtable],
            'pf.productfaq_id = main_table.productfaq_id',
            ['product_id']
        );
        $collection->addFieldToFilter('pf.productfaq_id', ['eq' => $productFaqId]);

        return $collection;
    }
    /**
     * Sets the store scope for configuration retrieval.
     *
     * @return string
     */
    public function setStoreScope()
    {
        return ScopeInterface::SCOPE_STORE;
    }
    /**
     * Retrieves whether customer login is required for viewing FAQs.
     *
     * @return mixed
     */
    public function getCustomerLoginRequired()
    {
        $config = 'productfaq/productfaq/customer_login_required';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves whether questions should be collapsible in the FAQ display.
     *
     * @return mixed
     */
    public function getQueCollapsible()
    {
        $config = 'productfaq/productfaq/questions_collapsible';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves whether customers can ask questions in the FAQ section.
     *
     * @return mixed
     */
    public function getAskQuestions()
    {
        $config = 'productfaq/productfaq/ask_questions';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves the status of reCAPTCHA in captcha settings for product FAQ.
     *
     * @return mixed
     */
    public function getReCaptchaStatus()
    {
        $config = 'productfaq/captcha_setting/captcha_enable';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves the status of captcha in the customer login and registration forms.
     *
     * @return mixed
     */
    public function getCaptchaFormStatusCustomer()
    {
        $config = 'customer/captcha/enable';

        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves the list of forms where captcha is enabled for customers.
     *
     * @return mixed
     */
    public function getCaptchaFormSelect()
    {
        $config = 'customer/captcha/forms';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves the name of the current store.
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }
    /**
     * Retrieves the email template configuration for notifying admin about new questions.
     *
     * @return mixed
     */
    public function getEmailTemplate()
    {
        $config = 'productfaq/productfaq/question_admin_email';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves the email template configuration for answering product FAQs.
     *
     * @return mixed
     */
    public function getAnswerEmailTemplate()
    {
        $config = 'productfaq/productfaq/answer_email';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves the email address where notifications about new questions are sent.
     *
     * @return mixed
     */
    public function getSentToEmail()
    {
        $config = 'trans_email/ident_general/email';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves the name associated with the email address for notifications.
     *
     * @return mixed
     */
    public function getSentToName()
    {
        $config = 'trans_email/ident_general/name';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves the list of email receivers for notifications about new questions.
     *
     * @return mixed
     */
    public function getMailRecievers()
    {
        $config = 'productfaq/productfaq/new_email_recievers';
        return $this->scopeConfig->getValue($config, $this->setStoreScope());
    }
    /**
     * Retrieves the page filter for filtering content.
     *
     * @return mixed
     */
    public function getPageFilter()
    {
        return $this->filterProvider->getPageFilter();
    }
}
