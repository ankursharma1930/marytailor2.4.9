<?php namespace Magebees\Productfeed\Controller\Adminhtml\Feed;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Exportrun extends \Magento\Backend\App\Action
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;
    protected $request;
    protected $_storeManager;
    protected $_configurable;
    protected $date;
    
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->request = $request;
        $this->_storeManager = $storeManager;
        $this->_configurable = $configurable;
        $this->date = $date;

        parent::__construct($context);
    }
 
        
    public function execute()
    {
        
        try {
            $data = [];
            $page = $this->getRequest()->getParam('page');
            $store_export_id = $this->getRequest()->getParam('store_id');
            $timestamp = $this->getRequest()->getParam('timestamp');
            $exportfiletype = $this->getRequest()->getParam('exportfiletype');
            $feed_id = $this->getRequest()->getParam('feed_id');
            $filename = $this->getRequest()->getParam('filename');
            $export_file_name = [];
            if ($exportfiletype=="csv") {
                $export_file_name = $this->_objectManager->create('\Magebees\Productfeed\Model\Csvexport')->getCSVFeedFile($page, $store_export_id, $timestamp, $feed_id, $filename);
            } elseif ($exportfiletype=="xml") {
                $export_file_name = $this->_objectManager->create('\Magebees\Productfeed\Model\Xmlexport')->getXMLFeedFile($page, $store_export_id, $timestamp, $feed_id, $filename);
            }
            $this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($export_file_name));
            return;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $data['error_message'] = $e->getMessage();
                $data['export_can_proceed'] = false;
                $this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($data));
                
                return;
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            $data['error_message'] = $e->getMessage();
            $data['export_can_proceed'] = false;
            $this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($data));
            return;
        }
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
