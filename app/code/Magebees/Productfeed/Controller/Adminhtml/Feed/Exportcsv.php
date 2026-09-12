<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magebees\Productfeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Exportcsv extends \Magento\Backend\App\Action
{
/**
 * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
 */
    protected $_productCollectionFactory;
    protected $_stockFilter;
    protected $scopeConfig;
    protected $date;

    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\CatalogInventory\Helper\Stock $stockFilter
    ) {
         $this->_productCollectionFactory = $productCollectionFactory;
         $this->_stockFilter = $stockFilter;
         $this->scopeConfig = $scopeConfig;
         $this->date = $date;
         $this->_productVisibility = $productVisibility;
         parent::__construct($context);
    }
    public function execute()
    {
        $feedId = $this->getRequest()->getParam('id');
        if ($feedId!="") {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $feed = $this->_objectManager('Magebees\Productfeed\Model\Feed');
    
            try {
                if ($feedId) {
                        $feed->load($feedId);
                    if (!$feed->getId()) {
                        $this->messageManager->addError(__('This feed no longer exists.'));
                        $this->_redirect('magebees_productfeed/*');
                        return;
                    }
                }
                $conditions_serialized = $feed->getConditionsSerialized();
                if ($feed->isJSON($conditions_serialized)) {
                            $conditions = json_decode($conditions_serialized, true);
                }
        
                $csv_content = $feed->getContent();
                if ($feed->isJSON($csv_content)) {
                        $csv_content = json_decode($csv_content, true);
                        $get_attr = $csv_content['attr'];
                } else {
                    $get_attr = "*";
                }
    
                if ($conditions['attributeset']!="") {
                    $attr_id = $conditions['attributeset'];
        
                    $_product_collection = $this->_objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')->create()->addAttributeToSelect("*")->addFieldToFilter('attribute_set_id', $attr_id);
                } else {
                    $_product_collection = $this->_objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')->create()->addAttributeToSelect("*");
                }
    
    
    
                if (isset($conditions['producttype'])) {
                    $_product_collection->addAttributeToFilter('type_id', ['in'=> $conditions['producttype']]);
                }
    
                if (isset($conditions['visibility'])) {
                    if (count($_product_collection)>0) {
                        $conditions['visibility'];
                        $_product_collection->setVisibility($this->_productVisibility->getVisibleInSiteIds());
                    }
                }
    
    
                if (!empty($conditions['status'])) {
                    if (count($_product_collection)>0) {
                        $_product_collection->addAttributeToFilter('status', ['in' => $conditions['status']]);
                    }
                }
                if (!empty($conditions['stock'])) {
                    if (count($_product_collection)>0) {
                        if ($conditions['stock']=="1") {
                            $_product_collection = $this->_stockFilter->addInStockFilterToCollection($_product_collection);
                        } elseif ($conditions['stock']=="0") {
                            $_product_collection = $this->addOutStockFilterToCollection($_product_collection);
                        }
                    }
                }
    
                if (!empty($conditions['categories'])) {
                    if (count($_product_collection)>0) {
                        $category_ids = $conditions['categories'];
                        $_product_collection->addCategoriesFilter(['in' => $category_ids]);
                    }
                }
    			$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
					if(!empty($scope_info['file_path']))
					{
					$file_path = $scope_info['file_path'];
					}else{
					$file_path = "var/export/";
					}
				
                if (count($_product_collection)>0) {
                    $vardir = \Magento\Framework\App\ObjectManager::getInstance();
                    $filesystem = $vardir->get('Magento\Framework\Filesystem');
                    $extvardir = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
                    //$exportdir = '/export';
					$exportdir = $file_path;
					
                    if ($feed->getType()=="csv") {
                        $Csvexport = $this->_objectManager->create('Magebees\Productfeed\Model\Csvexport');
                        $csv_content = $feed->getContent();
        
        
        
                        if ($feed->isJSON($csv_content)) {
                            $csv_content = json_decode($csv_content, true);
                            if (count($csv_content['name'])>0) {
                                if ($csv_content['csv_enclosure']=="double_quote") {
                                    $field_enclosed = '"';
                                } elseif ($csv_content['csv_enclosure']=="single_quote") {
                                    $field_enclosed = "'";
                                } elseif ($csv_content['csv_enclosure']=="space") {
                                    $field_enclosed = " ";
                                } elseif ($csv_content['csv_enclosure']=="none") {
                                    $field_enclosed = "";
                                }
                                if ($csv_content['csv_delimiter']=="comma") {
                                    $field_separated = ',';
                                } elseif ($csv_content['csv_delimiter']=="semicolon") {
                                    $field_separated = ';';
                                } elseif ($csv_content['csv_delimiter']=="pipe") {
                                    $field_separated = '|';
                                } elseif ($csv_content['csv_delimiter']=="tab") {
                                    $field_separated = '\t';
                                }
            
                                $heading = array_filter($csv_content['name']);
            
                                $export_file_name = $feed->getName()."-".$feed->getId().".csv";
								$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = "var/export/";
			}
								
                                $files = fopen($file_path.$export_file_name, "w+");
                                if (($field_enclosed!="") && ($field_separated!="")) {
                                    fputCsv($files, $heading, $field_enclosed, $field_separated);
                                } else {
                                    fputCsv($files, $heading);
                                }
            
                                fclose($files);
            
                            /* End of Export Headers */
                            /* For Export Data */
                                $files = fopen($file_path.$export_file_name, "a");
                                foreach ($_product_collection as $product) {
                                    $row = $Csvexport->csvProductRow($product, $csv_content);
                                    if (($field_enclosed!="") && ($field_separated!="")) {
                                        fputCsv($files, $row, $field_enclosed, $field_separated);
                                    } else {
                                        fputCsv($files, $row);
                                    }
                                }
                                fclose($files);
                                $feed->SetData('lastgeneratedate', $this->date->timestamp());
                                $feed->save();
                                $result = "";
                                $result = "Generated csv File :'".$file_path.$export_file_name;
                                $this->messageManager->addSuccess(__($result));
                                $this->_redirect('magebees_productfeed/*/');
                                return;
                            } else {
                                $this->messageManager->addError(
                                    __('There are no any column set for the feed')
                                );
            
                                $this->_redirect('magebees_productfeed/*/');
                                return;
                            }
                        } else {
                            $this->messageManager->addError(
                                __('There are no any content set for the feed')
                            );
            
                            $this->_redirect('magebees_productfeed/*/');
                            return;
                        }
                    } elseif ($feed->getType()=="xml") {
                        $xml_content = $feed->getContent();
                    }
                } else {
                    $this->messageManager->addError(__('There is no any products found'));
                    $this->_redirect('magebees_productfeed/*/');
                    return;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
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
                $this->_redirect('magebees_productfeed/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->messageManager->addError(
            __('Something went wrong while generate the feed data. Please review the error log.')
        );
        $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        $this->_redirect('magebees_productfeed/*/');
        return;
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
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
}
