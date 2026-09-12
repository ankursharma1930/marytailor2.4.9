<?php

namespace Dolphin\Productfaq\Controller\Adminhtml\Productfaq;

use Dolphin\Productfaq\Helper\ProductFaqHelper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Dolphin\Productfaq\Model\ResourceModel\Productfaq;
use Dolphin\Productfaq\Model\Productfaq as ModelProductFaq;
use Magento\Backend\Model\Session;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\Redirect;

class Save extends Action
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;
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
     * @var ResultFactory
     */
    protected $resultFactory;
    /**
     * @var LoggerInterface
     */
    protected $logLoggerInterface;
    /**
     * @var ProductFaqHelper
     */
    protected $ProductFaqHelper;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Class constructor.
     *
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param StateInterface $inlineTranslation
     * @param Data $ProductFaqHelper
     * @param LoggerInterface $loggerInterface
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        StateInterface $inlineTranslation,
        ProductFaqHelper $ProductFaqHelper,
        LoggerInterface $loggerInterface,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->dataPersistor = $dataPersistor;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->inlineTranslation = $inlineTranslation;
        $this->logLoggerInterface = $loggerInterface;
        $this->ProductFaqHelper = $ProductFaqHelper;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Check if action is allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $redirectBack = $this->getRequest()->getParam('back');
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $model = $this->_objectManager->create(ModelProductFaq::class);
            $id = $this->getRequest()->getParam('productfaq_id');
            if ($id) {
                $model->load($id);
            }
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage(__('This Productfaq no longer exists.'));
                
                return $resultRedirect->setPath('*/*/');
            }
            $model->setData($data);
            try {
                $model->save();
                $this->dataPersistor->clear('dolphin_productfaq_productfaq');
                $this->saveProducts($model, $data);
                if ($this->getRequest()->getParam('send') == 'send') {
                    $this->sendAdminMail();
                }
                $this->messageManager->addSuccessMessage(__('Question saved successfully.'));
                $this->_objectManager->get(Session::class)->setFormData(false);
                if ($this->getRequest()->getParam('back')) {

                    return $resultRedirect->setPath('*/*/edit', ['productfaq_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Productfaq.'));
            }
            $this->dataPersistor->set('dolphin_productfaq_productfaq', $data);

            return $resultRedirect->setPath('*/*/edit', ['productfaq_id' =>
                $this->getRequest()->getParam('productfaq_id')]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Save the products associated with the FAQ model.
     *
     * @param Productfaq $model
     * @param array $post
     * @return void
     */
    public function saveProducts($model, $post)
    {
        if (isset($post['category_products'])) {
            $productIds = json_decode($post['category_products'], true);
            if (isset($productIds['on'])) {
                unset($productIds['on']);
            }
            try {
                $oldProducts = (array) $model->getProducts($model);
                $newProducts = (array) $productIds;

                $connection = $this->resourceConnection->getConnection();

                $table = $this->resourceConnection
                    ->getTableName(Productfaq::TBL_ATT_PRODUCT);
                $insert = array_diff($newProducts, $oldProducts);
                $delete = array_diff($oldProducts, $newProducts);
                if ($delete) {
                    $where = ['productfaq_id = ?' => (int) $model->getId(), 'product_id IN (?)' => $delete];
                    $connection->delete($table, $where);
                }
                if ($insert) {
                    $data = [];
                    foreach ($insert as $product_id) {
                        $data[] = ['productfaq_id' => (int) $model->getId(), 'product_id' => (int) $product_id];
                    }
                    $connection->insertMultiple($table, $data);
                }
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the faq product.'));
            }
        }
    }
    /**
     * Send email notification to admin.
     *
     * @return Redirect
     */
    public function sendAdminMail()
    {
        $post = $this->getRequest()->getPost();
        $groupfaq = $post['group_faq'];
        if ($groupfaq == 0) {
            try {
                $this->inlineTranslation->suspend();

                $storeId = isset($post['store_faq']) && $post['store_faq'] ? $post['store_faq'] : null;
                $store = $this->storeManager->getStore($storeId)->getId();
                $select_email_template = $this->ProductFaqHelper->getAnswerEmailTemplate();
                $sentToEmail = $this->ProductFaqHelper->getSentToEmail();
                $sentToName = $this->ProductFaqHelper->getSentToName();
                $mail_recievers = $this->ProductFaqHelper->getMailRecievers();
                $email_count = 0;
                $transport = $this->transportBuilder
                    ->setTemplateIdentifier($select_email_template)
                    ->setTemplateOptions(['area' => 'frontend', 'store' => $store])
                    ->setTemplateVars(['question_faq' => $post['question_faq'],
                        'email_faq' => $post['email_faq'],
                        'answer_faq' => $post['answer_faq']])
                    ->setFrom(['email' => $sentToEmail, 'name' => $sentToName])
                    ->addTo($post['email_faq'])
                    ->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();
                $this->messageManager->addSuccess('Email sent successfully');
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
}
