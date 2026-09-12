<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Model;

use \Psr\Log\LoggerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use DOMDocument;
use DOMXPath;

class Schedule extends \Magento\Framework\Model\AbstractModel
{
    protected $logger;
    protected $feed;
    protected $_productCollectionFactory;
    
    protected $_product_data_collection=[];
    protected $_xml_product_data_collection=[];
    protected $scopeConfig;
    protected $date;
    protected $_filesystem;
    private $_Storemanager;
    protected $Csvexport;
    protected $_om;
    protected $_objectManager;
    private $_dateformat;
    private $_timeformat;
    private $_priceCurrency;
    private $_dynamicattributesResource;
    private $_productObj;
    private $_templates;
    private $visibility;
    protected $_systemStore;
    protected $main_file_name;
    
    
    
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        \Magebees\Productfeed\Model\Templates $templates,
        \Magebees\Productfeed\Model\Csvexport $Csvexport,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productCollectionFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magebees\Productfeed\Model\ResourceModel\Dynamicattributes $dynamicattributesResource,
        \Magento\Catalog\Model\Product $productObj,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\Catalog\Helper\Product $catalogProductHelper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $Storemanager,
        \Magebees\Productfeed\Model\Feed $feed
    ) {
        $this->logger = $logger;
        $this->_Storemanager = $Storemanager;
        $this->scopeConfig = $scopeConfig;
        $this->_productCollectionFactory = $_productCollectionFactory;
        
        $this->feed = $feed;
        $this->date = $date;
        $this->_filesystem = $filesystem;
        $this->Csvexport = $Csvexport;
        $this->_priceCurrency = $priceCurrency;
        $this->_dynamicattributesResource = $dynamicattributesResource;
        $this->_productObj = $productObj;
        $this->_templates = $templates;
        $this->visibility = $visibility;
    }

/**
 * Write to system.log
 *
 * @return void
 */
   
    
    public function getScheduleFeed()
    {
        $collection = $this->feed->getCollection()
                        ->addFieldToFilter('enable_schedule', 1);
        return $collection;
    }
    
                        
                    
    public function execute()
    {
        
        $schedule_Feed = $this->getScheduleFeed();
        
        if ($schedule_Feed->getSize()>0) {
            $schedule_feed_id = [];
            
            $totla_minutes = "";
            $current_day = date('w'); // output: current day.
            $current_minute = date('i'); // output: current day.
            $current_hours = date('H'); // output: current day.
            $current_time = date('H : i : s');
            $totla_minutes = ($current_hours*60)+($current_minute);
            $this->logger->info('current_time ::'.$current_time);
            $this->logger->info('current_minute ::'.$current_minute);
            $this->logger->info('current_hours ::'.$current_hours);
            $this->logger->info('totla_minutes::'.$totla_minutes);
            foreach ($schedule_Feed as $feed) :
                if ($this->isJSON($feed->getSchedule())) {
                    $schedule_feed_info = json_decode($feed->getSchedule(), true);
                    if (isset($schedule_feed_info['days'])) {
                         $schedule_days = $schedule_feed_info['days'];
                        if (in_array($current_day, $schedule_days)) {
                            if (isset($schedule_feed_info['times'])) {
                                $schedule_times = $schedule_feed_info['times'];
                                foreach ($schedule_times as $tKey => $tValue) :
                                //$this->logger->info('tValue ::'.$tValue);
                                    if ($tValue <= $totla_minutes) {
                                        if (isset($schedule_feed_info['feedstatus'])) {
                                            $schedule_feedstatus = $schedule_feed_info['feedstatus'];
                                            if ($schedule_feedstatus[$tValue]['status']==0) {
                                            //$this->logger->info('tValue Feed Id::'.$feed->getId());
                                                $schedule_feed_id[] = ['id'=>$feed->getId(),'time'=>$tValue];
                                            }
                                        }
                                    }
                                endforeach;
                            }
                        }
                    }
                }
            endforeach;
            //$schedule_feeds = array_unique($schedule_feed_id);
            
            
            
            foreach ($schedule_feed_id as $key => $feedValue) :
                    $this->logger->info('schedule_feed_id::'.$feedValue['id']);
                    $this->logger->info('schedule_feed_time::'.$feedValue['time']);
                $result = $this->executeFeedWithTime($feedValue['id'], $feedValue['time']);
                $this->logger->info('schedule_feed_id::result'.$result);
                if ($result==1) {
                    $feed = $this->feed->load($feedValue['id']);
                    $schedule_feed_info = json_decode($feed->getSchedule(), true);
                    if (isset($schedule_feed_info['feedstatus'])) {
                        $schedule_feed_info['feedstatus'][$feedValue['time']]['status']="1";
                    }
                    $last_times = '';
                    
                    if (isset($schedule_feed_info['times'])) {
                        $last_schedule_times = $schedule_feed_info['times'];
                        $last_times = end($last_schedule_times);
                        if ($feedValue['time']==$last_times) {
                            $feed_schedule_time_status = [];
                        
                            foreach ($last_schedule_times as $timeKey => $timeValue) :
                                $feed_schedule_time_status[$timeValue] = ['status'=>'0'];
                            endforeach;
                            $schedule_feed_info['feedstatus'] = $feed_schedule_time_status;
                        }
                    }
                    
                    //$schedule_feedstatus[$feedValue['time']]['status']="1";
                    $schedule = json_encode($schedule_feed_info);
                    $feed->setSchedule($schedule);
                    $feed->save();
                }
                $this->logger->info('Day Wise Feed Id ::'.$feedValue['id']);
                $this->logger->info('Day Wise Feed time ::'.$feedValue['time']);
            endforeach;
        } else {
            $this->logger->info('There is no any feed Schedule.');
        }
    }
    public function executeFeedWithTime($feed_id, $time)
    {
        $feed = $this->feed->load($feed_id);
        $data = [];
        $can_proceed = false;
        $count = 0;
        $conditions_serialized = $feed->getConditionsSerialized();
        if ($feed->isJSON($conditions_serialized)) {
            $conditions = json_decode($conditions_serialized, true);
        }
        $store_id = $feed->getStoreview();
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
        $this->logger->info("Count Frist::".count($_product_collection));
        if ($count>0) {
            $can_proceed=true;
            $current_time = $this->date->gmtDate();
            $data['timestamp'] = $current_time;
            $download_url_info = $feed->getDownloadUrl();
            if ($this->isJSON($download_url_info)) {
                $download_url_info = json_decode($download_url_info, true);
                if (isset($download_url_info['feedname'])) {
                    $feedname=preg_replace('/\s+/', '', $download_url_info['feedname']);
                    $this->feedname = $feedname;
                } else {
                    $feedname=preg_replace('/\s+/', '', $this->feed->getName().".".$this->feed->getType());
                    $this->feedname = $feedname;
                }
            }
            $download_url_info['feedstatus'] = "0";
            $download_url_info['total_products'] = $count;
            
            
            $feedname=preg_replace('/\s+/', '', $this->feed->getName().$current_time.".".$this->feed->getType());
            $download_url_info['download_filename'] = $feedname;
            
            $download_url_info = json_encode($download_url_info, true);
            $this->feed->setDownloadUrl($download_url_info);
            $this->feed->setLastgeneratedate(date("Y-m-d H:m:s"));
            $this->feed->save();
            $store_export_id = $store_id;
            $timestamp = $current_time;
            $exportfiletype = $this->feed->getType();
            $feed_id = $this->feed->getId();
            $filename = $this->feedname;
            $path = '';
            $export_result = '';
            if ($exportfiletype=="csv") {
                $export_result = $this->getCSVFeedFile($store_id, $timestamp, $feed_id, $filename);
            } elseif ($exportfiletype=="xml") {
                $export_result = $this->getXMLFeedFile($store_id, $timestamp, $feed_id, $filename);
            }
        }
            $this->logger->info('Cron Works Schedule completed');
            return $export_result;
    }
    public function getXMLFeedFile($store_id, $timestamp, $feed_id, $filename)
    {
        $this->logger->info('Cron Works Schedule getXMLFeedFile');
        if ($feed_id!="") {
            $this->feed->load($feed_id);
            $feedname=preg_replace('/\s+/', '', $this->feed->getName());
            $this->main_file_name = $feedname;
    
    
            $store_id = $this->feed->getStoreview();
            $conditions_serialized = $this->feed->getConditionsSerialized();
            $format_serialized = $this->feed->getFormatSerialized();
            $feed_data = $this->feed->getData();
            if ($this->feed->isJSON($conditions_serialized)) {
                $conditions = json_decode($conditions_serialized, true);
            }
    
            if ($this->feed->isJSON($format_serialized)) {
                $this->field_format = json_decode($format_serialized, true);
            }
    
            $this->_dateformat = str_replace('"', "", $this->field_format['dateformat']);
            $this->_timeformat = str_replace('"', "", $this->field_format['timeformat']);
    
    
    
            $xml_content = $this->feed->getContent();
            $download_url_info = $this->feed->getDownloadUrl();
            if ($this->isJSON($download_url_info)) {
                $download_url_info = json_decode($download_url_info, true);
                if (isset($download_url_info['feedname'])) {
                    $feedname=preg_replace('/\s+/', '', $download_url_info['feedname']);
                    $this->feedname = $feedname;
                } else {
                    $feedname=preg_replace('/\s+/', '', $this->feed->getName().".".$this->feed->getType());
                    $this->feedname = $feedname;
                }
            }
            $this->main_file_name = $this->feedname;
    
            $this->logger->info('main_file_name ::'.$this->main_file_name);
    
            if ($conditions['attributeset']!="*") {
                $attr_id = $conditions['attributeset'];
                    $_product_collection = $this->_productCollectionFactory->create();
                    $_product_collection->addAttributeToSelect("*");
                    $_product_collection->addFieldToFilter('attribute_set_id', $attr_id);
                    $_product_collection->addUrlRewrite();
            } elseif ($conditions['attributeset']=="*") {
                $_product_collection = $this->_productCollectionFactory->create();
                $_product_collection->addAttributeToSelect("*");
                $_product_collection->addUrlRewrite();
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
        		$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
            if (count($_product_collection)>0) {
                    $extvardir = $this->_filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
                    //$exportdir = '/export';
					$exportdir = $file_path;
                    $xml_content = $this->feed->getContent();
                if ($this->feed->isJSON($xml_content)) {
                    $xml_content = json_decode($xml_content, true);
                }
                    $filename = $this->feed->getName().".xml";
                    $extvardir = $this->_filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
                    //$exportdir = '/export';
					$exportdir = $file_path;
                    $extvardir->create($exportdir);
                    $extvardir->changePermissions($exportdir, \Magento\Framework\Filesystem\DriverInterface::WRITEABLE_DIRECTORY_MODE);
                    $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
                    $path = $reader->getAbsolutePath($exportdir);
                    $this->_om  = \Magento\Framework\App\ObjectManager::getInstance();
                    $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $Xmlparser = $this->_objectManager->create('Magebees\Productfeed\Model\Xmlparser');
                    $xml_to_array = $this->_objectManager->create('Magebees\Productfeed\Model\XmlToArray');
                    $array_to_xml = $this->_objectManager->create('Magebees\Productfeed\Model\ArrayToXml');
                    $array_to_xmlbuild = $this->_objectManager->create('Magebees\Productfeed\Model\ArrayToXmlbuild');
                    $xml_contentfilename = $this->feed->getName()."-content.xml";
        
                    $xml_header = trim($xml_content['xml_header']);
                    $item_start_tag = trim($xml_content['feed_xml_item']);
                    $item_end_tag = trim($xml_content['feed_xml_item']);
                    
                    $xml_body = trim($xml_content['xml_body']);
                    $xml_footer = trim($xml_content['xml_footer']);
                    $this->feed_xml_item = $item_start_tag;
                    /* Check If Item tag have any attribute set then work on that...*/
                    $item_tag = (explode("=", $item_start_tag));
                if (isset($item_tag[0]) && ($item_tag[0]!="")) {
                    $tag = (explode(" ", $item_tag[0]));
                    $item_start_tag = trim($xml_content['feed_xml_item']);
                    $item_end_tag = $tag[0];
                    $this->feed_xml_item = $item_end_tag;
                }
                    
                    $this->xml_header = $xml_header;
                    $this->xml_footer = $xml_footer;
            
            
                    
            
                if (file_exists($path.$xml_contentfilename)) {
                    $xmldata = $myxmlfilecontent = file_get_contents($path.$xml_contentfilename);
                } else {
                    $xml_content = trim($xml_header)."\n<".$item_start_tag.">\n".$xml_body."\n</".$item_end_tag.">\n".$xml_footer;
                    $tempFile = fopen($path.$xml_contentfilename, "w");
                    fwrite($tempFile, $xml_content);
                    fclose($tempFile);
                    $xmldata = $myxmlfilecontent = file_get_contents($path.$xml_contentfilename);
                }
            
                        $xml_item = [];
                        $_product_collection->setStore($store_id);
                        $this->_xml_product_data_collection=[];
                foreach ($_product_collection as $product) {
                    $product_xml="";
                    $this->xmlfield = null;
                    $xml_array = $xml_to_array->createArray($xmldata, $this->feed_xml_item, $product, $feed_data);

                    $xml_item_content = $Xmlparser->find_xml_item($xml_array, $this->feed_xml_item);
                
                    $xml_item_content = $Xmlparser->find_xml_item($xml_array, $this->feed_xml_item);
                    $xml =  $array_to_xml->createXML($this->feed_xml_item, $xml_item_content);
                    $product_xml = str_replace('<?xml version="1.0"?>', '', $xml->saveXML());
                    array_push($this->_xml_product_data_collection, $product_xml);
                }
            
                        $f_name= $this->main_file_name;
                        $file_name = $reader->getAbsolutePath($exportdir.$f_name);
                        $temp_file = $file_name;
            
                if (file_exists($file_name)) {
                    unlink($file_name);
                }
                        $product_xml_file = fopen($temp_file, "w");
            
                        $codes = ['{{StoreManager.baseurl}}',
                        '{{StoreManager.basesecureurl}}',
                        '{{StoreManager.frontname}}',
                        '{{StoreManager.formattedaddress}}',
                        '{{StoreManager.basecurrencycode}}',
                        '{{StoreManager.defaultcurrencycode}}',
                        '{{StoreManager.currentcurrencycode}}','{{date}}','{{time}}'
                        ];
                        $accurate = [$this->_Storemanager->getStore($store_id)->getBaseUrl(),
                        $this->_Storemanager->getStore($store_id)->getBaseSecureUrl(),
                        $this->_Storemanager->getStore($store_id)->getFrontendName(),
                        $this->_Storemanager->getStore($store_id)->getFormattedAddress(),
                        $this->_Storemanager->getStore($store_id)->getBaseCurrencyCode(),
                        $this->_Storemanager->getStore($store_id)->getDefaultCurrencyCode(),
                        $this->_Storemanager->getStore($store_id)->getCurrentCurrencyCode(),
                        date($this->_dateformat),
                        time($this->_timeformat)
                        ];
                        $repalced_header =  str_replace($codes, $accurate, $this->xml_header);
                        file_put_contents($temp_file, $repalced_header);
                        foreach ($this->_xml_product_data_collection as $product) {
                            file_put_contents($temp_file, $product, FILE_APPEND);
                        }
                        file_put_contents($temp_file, $this->xml_footer, FILE_APPEND);
                        fclose($product_xml_file);
            
            
            
                        /* Code added For Remove Blank tag from xml */
            
            
                        $xml_final_file_content = file_get_contents($temp_file);
                        $xml_file_content = str_replace("<![CDATA[]]>", "", $xml_final_file_content);
                        $this->xmlcontent = new DOMDocument;
                        $this->xmlcontent->preserveWhiteSpace = false;
                        $this->xmlcontent->loadxml($xml_file_content);
                        $xpath = new DOMXPath($this->xmlcontent);
            
                        while (($notNodes = $xpath->query('//*[not(node())]')) && ($notNodes->length)) {
                            foreach ($notNodes as $node) {
                                $node->parentNode->removeChild($node);
                            }
                        }
                        $this->xmlcontent->formatOutput = true;
            
                        $fp = fopen($temp_file, "w");
                        fwrite($fp, $this->xmlcontent->savexml());
                        fclose($fp);
            
                        if (file_exists($path.$xml_contentfilename)) {
                            unlink($path.$xml_contentfilename);
                        }
                        /* Code added end For Remove Blank tag from xml */
            
            
            
                        /* Code added For Set Feed Status Flag */
                        $download_url_info = [];
                        if ($this->feed->getId()) {
                            $download_url_info = $this->feed->getDownloadUrl();
                            if ($this->isJSON($download_url_info)) {
                                if (isset($download_url_info['feedname'])) {
                                    $this->feedname = $download_url_info['feedname'];
                                }
                                $download_url_info = json_decode($download_url_info, true);
                                $download_url_info['feedstatus'] = "1";
                                $download_url_info['feedname'] = trim($this->feedname);
                        
                                $this->feed->setDownloadUrl(json_encode($download_url_info));
                                $this->feed->setLastgeneratedate($timestamp);
                                $this->feed->save();
                            }
							/* Code Start For Upload Feed File On other server Using FTP */	
				$ftp_settings = $this->feed->getFtpSettings();
				if($this->isJSON($ftp_settings)){
				$ftp_settings_info = json_decode($ftp_settings, true);
					if($ftp_settings_info['enable_ftp_upload'])
					{
					$ori_file_path = $path.$this->feedname;
					$feedname = $this->feedname;
					$helper = $this->_objectManager->get('\Magebees\Productfeed\Helper\Data'); 
					if($ftp_settings_info['protocol']=='sftp')
					{
					$uploaded = $helper->sftp_file_upload($feedname,$ori_file_path,$ftp_settings_info);
					}else if($ftp_settings_info['protocol']=='ftp')
					{
					$uploaded = $helper->ftp_file_upload($feedname,$ori_file_path,$ftp_settings_info);
					}
					}
				}
				/* Code End For Upload Feed File On other server Using FTP */	
							
                        }
                        /* Code completed For Set Feed Status Flag */
                        return 1;
                        $this->logger->info('Cron Works Schedule getXMLFeedFile Complted');
            } else {
                $this->logger->info('There is no any products found');
                return 0;
            }
        } else {
            $this->logger->info('There is no any feed found');
            return 0;
        }
        $this->logger->info('Cron Works Schedule getXMLFeedFile Complted');
    }
    public function getCSVFeedFile($store_id, $timestamp, $feed_id, $filename)
    {
    
        $this->logger->info('Cron Works Schedule getCSVFeedFile');
        if ($feed_id!="") {
            $this->feed->load($feed_id);
            $conditions_serialized = $this->feed->getConditionsSerialized();
            if ($this->feed->isJSON($conditions_serialized)) {
                $conditions = json_decode($conditions_serialized, true);
            }
            $format_serialized = $this->feed->getFormatSerialized();
            if ($this->feed->isJSON($format_serialized)) {
                $this->field_format = json_decode($format_serialized, true);
            }
    
            if ($conditions['attributeset']!="*") {
                $attr_id = $conditions['attributeset'];
                    $_product_collection = $this->_productCollectionFactory->create();
                    $_product_collection->addAttributeToSelect("*");
                    $_product_collection->addFieldToFilter('attribute_set_id', $attr_id);
                    $_product_collection->addUrlRewrite();
            } elseif ($conditions['attributeset']=="*") {
                $_product_collection = $this->_productCollectionFactory->create();
                $_product_collection->addAttributeToSelect("*");
                $_product_collection->addUrlRewrite();
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
        
        
    

            $feed_data = $this->feed->getData();
            $csv_content = $this->feed->getContent();
            $this->main_file_name = str_replace(' ', '_', $this->feed->getName());
    
            if ($this->feed->isJSON($csv_content)) {
                    $csv_content = json_decode($csv_content, true);
                    $get_attr = $csv_content['attr'];
            } else {
                $get_attr = "*";
            }
            //
            $download_url_info = $this->feed->getDownloadUrl();
            if ($this->isJSON($download_url_info)) {
                $download_url_info = json_decode($download_url_info, true);
                if (isset($download_url_info['feedname'])) {
                    $this->feedname = $download_url_info['feedname'];
                } else {
                    $this->feedname = trim($this->feed->getName().".".$this->feed->getType());
                }
            }
            $this->store_id = $this->feed->getStoreview();
            $this->logger->info("Count::".count($_product_collection));
            if (count($_product_collection)>0) {
                $extvardir = $this->_filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
				$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
				
                $exportdir = $file_path;
                 $extvardir->changePermissions($exportdir, \Magento\Framework\Filesystem\DriverInterface::WRITEABLE_DIRECTORY_MODE);
                $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        
                $csv_content = $this->feed->getContent();
        
                if ($this->feed->isJSON($this->feed->getFormatSerialized())) {
                        $this->field_format = json_decode($this->feed->getFormatSerialized(), true);
                }
                if ($this->feed->isJSON($csv_content)) {
                    $csv_content = json_decode($csv_content, true);
                    if (count($csv_content['name'])>0) {
                        $this->store_id = $feed_data['storeview'];
                        $_product_collection->setStore($this->store_id);
                        $this->_product_data_collection=[];
                        foreach ($_product_collection as $product) {
                            $this->logger->info("CSV URL::".$product->getProductUrl());

                            $this->logger->info('Cron Works csvProductRow Start'.$product->getId());
                            $row =  $this->Csvexport->csvProductRow($product, $csv_content, $this->store_id, $this->field_format);
                            $this->logger->info('Cron Works csvProductRow Complted'.$product->getId());
                            array_push($this->_product_data_collection, $row);
                        }
                        $current_time = $timestamp;
            
                        $this->cws_csv_feed_header=[];
                        foreach ($this->_product_data_collection as $product) {
                            foreach (array_keys($product) as $k => $v) {
                                if (!in_array($v, $this->cws_csv_feed_header)) {
                                    array_push($this->cws_csv_feed_header, $v);
                                }
                            }
                        }
            
                        $file_name = $reader->getAbsolutePath($file_path.$this->feedname);
                    //$file_name = $reader.'/export/'.$f_name;
                        if (file_exists($file_name)) {
                            unlink($file_name);
                        }
						 if ($csv_content['csv_delimiter']=="comma") {
                            $field_separated = ",";
                        } elseif ($csv_content['csv_delimiter']=="semicolon") {
                            $field_separated = ";";
                        } elseif ($csv_content['csv_delimiter']=="pipe") {
                            $field_separated = "|";
                        } elseif ($csv_content['csv_delimiter']=="tab") {
                            $field_separated = "\t";
                        }
                        if ($csv_content['csv_enclosure']=="double_quote") {
                            $field_enclosed = '"';
                        } elseif ($csv_content['csv_enclosure']=="single_quote") {
                            $field_enclosed = "'";
                        } elseif ($csv_content['csv_enclosure']=="space") {
                            $field_enclosed = " ";
                        } elseif ($csv_content['csv_enclosure']=="none") {
                            $field_enclosed = '"';
                        }
						
                        $fp = fopen($file_name, 'w');
                        $header=true;
                        $temp_order_data=[];
            
                        if ($csv_content['csv_header']=="1") {
                             fputcsv($fp, array_values($this->cws_csv_feed_header), $field_separated, $field_enclosed);
							
                        }
                       
            
                        foreach ($this->_product_data_collection as $product) {
                            $o_data=array_fill_keys(array_values($this->cws_csv_feed_header), '');
                            foreach ($product as $o_key => $o_val) {
                                if (in_array($o_key, $this->cws_csv_feed_header)) {
                                    $o_data[$o_key]=$o_val;
                                }
                            }
                            fputcsv($fp, array_values($o_data), $field_separated, $field_enclosed);
                        }
                        fclose($fp);
            
                    /* Code added For Set Feed Status Flag */
                        $download_url_info = [];
                        if ($this->feed->getId()) {
                            $download_url_info = $this->feed->getDownloadUrl();
                            if ($this->isJSON($download_url_info)) {
                                if (isset($download_url_info['feedname'])) {
                                    $this->feedname = $download_url_info['feedname'];
                                }
                                $download_url_info = json_decode($download_url_info, true);
                                $download_url_info['feedstatus'] = "1";
                                $download_url_info['feedname'] = trim($this->feedname);
                        
                                $this->feed->setDownloadUrl(json_encode($download_url_info));
                                $this->feed->setLastgeneratedate($current_time);
                                $this->feed->save();
                            }
							/* Code Start For Upload Feed File On other server Using FTP */	
							$ftp_settings = $this->feed->getFtpSettings();
							if($this->isJSON($ftp_settings)){
							$ftp_settings_info = json_decode($ftp_settings, true);
								if($ftp_settings_info['enable_ftp_upload'])
								{
								$ori_file_path = $path.$this->feedname;
								$feedname = $this->feedname;
								$helper = $this->_objectManager->get('\Magebees\Productfeed\Helper\Data'); 
								$uploaded = $helper->ftp_file_upload($feedname,$ori_file_path,$ftp_settings_info);
								}
							}
				/* Code End For Upload Feed File On other server Using FTP */	
					
                        }
                            return 1;
                    /* Code completed For Set Feed Status Flag */
            
                        $this->logger->info('Cron Works csvProductRow File Complted');
                        $this->logger->info('Cron Works Schedule getCSVFeedFile Complted');
                    } else {
                        $this->logger->info('There are no any content set for the feed');
                        return 0;
                    }
                }
            } else {
                $this->logger->info('There is no any products found');
                return 0;
            }
        } else {
            $this->logger->info('There is no any feed found');
            return 0;
        }
        $this->logger->info('Cron Works Schedule getCSVFeedFile Complted');
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
}
