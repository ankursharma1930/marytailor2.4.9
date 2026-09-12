<?php
namespace Magebees\Productfeed\Controller\Adminhtml\Feed;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Exportrecordcount extends \Magento\Backend\App\Action
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;
    protected $_productCollectionFactory;
    protected $_stockFilter;
    protected $scopeConfig;
    protected $date;
    protected $_feed;
    protected $_ObjectManager;	protected $_productVisibility;	protected $feedname;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \Magebees\Productfeed\Model\Feed $feed
    ) {
         $this->_productCollectionFactory = $_productCollectionFactory;
        $this->_stockFilter = $stockFilter;
        $this->scopeConfig = $scopeConfig;
        $this->_productVisibility = $productVisibility;
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_feed = $feed;
        
        $this->date = $date;

        parent::__construct($context);
    }
 
        
    public function execute()
    {
        /* Count Total Exported Products for export Products,*/
        $feedId = $this->getRequest()->getParam('feed_id');
        if ($feedId!="") {
            try {
                if ($feedId) {
                        $this->_feed->load($feedId);
                    if (!$this->_feed->getId()) {
                        $this->messageManager->addError(__('This feed no longer exists.'));
                        $this->_redirect('magebees_productfeed/*');
                        return;
                    }
                        $data = [];
                        $can_proceed = false;
                        $count = 0;
                        $conditions_serialized = $this->_feed->getConditionsSerialized();
                    if ($this->_feed->isJSON($conditions_serialized)) {
                        $conditions = json_decode($conditions_serialized, true);
                    }
                        $store_id = $this->_feed->getStoreview();
                    if ($conditions['attributeset']!="*") {
                            $attr_id = $conditions['attributeset'];
                        $_product_collection = $this->_productCollectionFactory->create();
                        $_product_collection->addAttributeToSelect("*");
                        $_product_collection->addFieldToFilter('attribute_set_id', $attr_id);
                    } elseif ($conditions['attributeset']=="*") {
                        $_product_collection = $this->_productCollectionFactory->create();
                        $_product_collection->addAttributeToSelect("*");
                    }
                            $_product_collection->clear()
                                   ->addAttributeToFilter('type_id', ['in' => $conditions['producttype']])
                                   ->addAttributeToFilter('visibility', ['in' => $conditions['visibility']])
                                   ->addAttributeToFilter('status', ['in' => $conditions['status']]);
                    if (isset($conditions['categories'])) {
                        $categoryFilter = [];
                        foreach ($conditions['categories'] as $key => $id) :
                            $categoryFilter['eq'][] = $id;
                        endforeach;

                        if (count($categoryFilter['eq']) > 0) {
                            $_product_collection->addCategoriesFilter($categoryFilter);
                        }
                    }
                            
                    if ($conditions['stock']!="0") {
                        if ($conditions['stock']=="1") {
                            $_product_collection = $this->addInStockFilterToCollection($_product_collection);
                        } elseif ($conditions['stock']=="2") {
                            $_product_collection = $this->addOutStockFilterToCollection($_product_collection);
                        }
                    }
                            $_product_collection->addStoreFilter($store_id);
                            $_product_collection->load();
                            $count = $_product_collection->clear()->getSize();
                    if ($count>0) {
                        $can_proceed=true;
                        $current_time = str_replace(" ", "-", $this->date->gmtDate());
                        $current_time = str_replace(":", "-", $current_time);
                        $data['timestamp'] = $current_time;
                            
                            
                        $download_url_info = $this->_feed->getDownloadUrl();
                        if ($this->isJSON($download_url_info)) {
                            $download_url_info = json_decode($download_url_info, true);
                            if (isset($download_url_info['feedname'])) {
                                    $this->feedname = $download_url_info['feedname'];
                            } else {
                                $this->feedname = $this->_feed->getName().".".$this->_feed->getType();
                            }
                        }
                        $feedname=preg_replace('/\s+/', '', $this->feedname);
                            
                        $download_url_info['feedname'] = $feedname;
                        $download_url_info['feedstatus'] = "0";
                        $download_url_info['total_products'] = $count;
                        $download_url_info['download_filename'] = str_replace(' ', '_', $this->_feed->getName()).$current_time.".".$this->_feed->getType();
                        $download_url_info = json_encode($download_url_info, true);
                        $this->_feed->setDownloadUrl($download_url_info);
                        $this->_feed->setLastgeneratedate(date("Y-m-d H:m:s"));
                            
                        $this->_feed->save();
                    }
                    if ($count>200) {
                        $data['splitExport']=true;
                    } else {
                        $data['splitExport']=false;
                    }
                        $data['export_can_proceed']=$can_proceed;
                        $data['totalProduct']=$count;
                    if ($count==0) {
                        $data['error_message'] = 'There are no any products found';
                    }
                        $this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($data));
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $data['error_message'] = $e->getMessage();
                $data['export_can_proceed'] = false;
                $this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($data));
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('magebees_productfeed/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('magebees_productfeed/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __($e->getMessage())
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $data['error_message'] = $e->getMessage();
                $data['export_can_proceed'] = false;
                $this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($data));
                $this->_redirect('magebees_productfeed/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        } else {
            $this->messageManager->addError(
                __('Something went wrong while generate the feed data. Please review the error log.')
            );
            $this->_redirect('magebees_productfeed/*/');
            return;
        }
    }
    
    
    public function addOutStockFilterToCollection($collection)
    {
       
        
        $manageStock = $this->scopeConfig->getValue(
            \Magento\CatalogInventory\Model\Configuration::XML_PATH_MANAGE_STOCK,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $cond = [
            '{{table}}.use_config_manage_stock = 0 AND {{table}}.manage_stock=1 AND {{table}}.is_in_stock=0',
            '{{table}}.use_config_manage_stock = 0 AND {{table}}.manage_stock=0'
        ];

        if ($manageStock) {
            $cond[] = '{{table}}.use_config_manage_stock = 1 AND {{table}}.is_in_stock=0';
        } else {
            $cond[] = '{{table}}.use_config_manage_stock = 0';
        }

            $collection->joinField(
                'inventory_in_stock',
                'cataloginventory_stock_item',
                'is_in_stock',
                'product_id=entity_id',
                '(' . join(') OR (', $cond) . ')'
            );
        return $collection;
    }
    public function addInStockFilterToCollection($collection)
    {
        
        $manageStock = $this->scopeConfig->getValue(
            \Magento\CatalogInventory\Model\Configuration::XML_PATH_MANAGE_STOCK,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $cond = [
            '{{table}}.use_config_manage_stock = 0 AND {{table}}.manage_stock=1 AND {{table}}.is_in_stock=1',
            '{{table}}.use_config_manage_stock = 0 AND {{table}}.manage_stock=0'
        ];

        if ($manageStock) {
            $cond[] = '{{table}}.use_config_manage_stock = 1 AND {{table}}.is_in_stock=1';
        } else {
            $cond[] = '{{table}}.use_config_manage_stock = 1';
        }

        $collection->joinField(
            'inventory_in_stock',
            'cataloginventory_stock_item',
            'is_in_stock',
            'product_id=entity_id',
            '(' . join(') OR (', $cond) . ')'
        );
        return $collection;
    }
    public function isJSON($string)
    {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
