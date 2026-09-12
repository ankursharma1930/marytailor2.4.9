<?php
namespace Dolphin\Productfaq\Controller\Index;

use Dolphin\Productfaq\Model\ProductfaqFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Action\Action;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Psr\Log\LoggerInterface;
use Dolphin\Productfaq\Model\ProductsFactory;
use Dolphin\Productfaq\Helper\ProductFaqHelper;
use Magento\Framework\Controller\Result\Redirect;

class Save extends Action
{
    /**
     * @var ProductfaqFactory
     */
    protected $productfaqFactory;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logLoggerInterface;

    /**
     * @var ProductsFactory
     */
    protected $proModel;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var ProductFaqHelper
     */
    protected $ProductFaqHelper;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ProductfaqFactory $productfaqFactory
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param StateInterface $inlineTranslation
     * @param LoggerInterface $loggerInterface
     * @param ProductsFactory $proModel
     * @param ProductFaqHelper $ProductFaqHelper
     * @param DataPersistorInterface $dataPersistor
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        Context $context,
        ProductfaqFactory $productfaqFactory,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        StateInterface $inlineTranslation,
        LoggerInterface $loggerInterface,
        ProductsFactory $proModel,
        ProductFaqHelper $ProductFaqHelper,
        DataPersistorInterface $dataPersistor,
        CustomerFactory $customerFactory
    ) {
        parent::__construct($context);
        $this->productfaqFactory = $productfaqFactory;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->inlineTranslation = $inlineTranslation;
        $this->logLoggerInterface = $loggerInterface;
        $this->proModel = $proModel;
        $this->ProductFaqHelper = $ProductFaqHelper;
        $this->dataPersistor = $dataPersistor;
        $this->messageManager = $context->getMessageManager();
        $this->customerFactory = $customerFactory;
    }

    /**
     * Execute the product FAQ submission process
     *
     * @return Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $data['customer_id'] = $this->getCustomerIdByEmail($data['email_faq']);

        try {
            $faqdata = $this->productfaqFactory->create();
            $faqdata->setData($data);
            $faqdata->save();
            $productData = $this->proModel->create();
            $productData->setProductfaqId($faqdata->getProductfaqId());
            $productData->setProductId($faqdata->getProductId());
            $productData->save();
            $this->dataPersistor->clear('dolphin_productfaq_productfaq');
            $this->sendMail();
            $this->messageManager->
                addSuccessMessage(__('Your question has been submitted.
            Will respond to your question soon.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__('please try again. Form Not Submit'));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }

    /**
     * Retrieve customer ID by email
     *
     * @param string $email
     * @return int Customer
     */
    public function getCustomerIdByEmail(string $email)
    {
        $websiteID = $this->storeManager->getStore()->getWebsiteId();
        $customerId = 0;
        $customerData = $this->customerFactory->create()->setWebsiteId($websiteID)->loadByEmail($email);
        if ($customerData) {
            $customerId = (int)$customerData->getId();
        }
        
        return $customerId;
    }

    /**
     * Method to send email with the provided data
     *
     * @return Redirect
     */
    public function sendMail()
    {
        $post = $this->getRequest()->getPost();
        try {
            //Send Mail
            $this->inlineTranslation->suspend();
            $store = $this->storeManager->getStore()->getId();
            $select_email_template = $this->ProductFaqHelper->getEmailTemplate();
            $sentToEmail = $this->ProductFaqHelper->getSentToEmail();
            $sentToName = $this->ProductFaqHelper->getSentToName();
            $mail_recievers = $this->ProductFaqHelper->getMailRecievers();
            $email_count = 0;
            if ($mail_recievers != '') {
                $mail_recievers = explode(',', $mail_recievers);
                foreach ($mail_recievers as $valid_email) {
                    $valid_email = preg_replace('/\s+/', '', $valid_email);
                    if (filter_var($valid_email, FILTER_VALIDATE_EMAIL)) {
                        $transport = $this->transportBuilder
                            ->setTemplateIdentifier($select_email_template)
                            ->setTemplateOptions(['area' => 'frontend', 'store' => $store])
                            ->setTemplateVars(['question_faq' => $post['question_faq'],
                                'email_faq' => $post['email_faq'],
                                'name_faq' => $post['name_faq'],
                                'product_sku' => $post['product_sku'],
                                'product_name' => $post['product_name']])
                            ->setFrom(['email' => $sentToEmail, 'name' => $sentToName])
                            ->addTo($valid_email)
                            ->getTransport();
                        $transport->sendMessage();
                    }
                    $email_count++;
                }
            } else {
                if ($email_count != 0) {
                    $this->messageManager->addError(__('Admin Email are Invalid or Empty.'));
                    $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    $resultRedirect->setUrl($this->_redirect->getRefererUrl());

                    return $resultRedirect;
                }
            }
            $this->inlineTranslation->resume();
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());

            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('E-mail send failed, please try again.'));
            $this->logLoggerInterface->debug($e->getMessage());
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());

            return $resultRedirect;
        }
    }
}
