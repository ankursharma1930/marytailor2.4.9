<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Xmlexport extends \Magento\Framework\Model\AbstractModel
{
      /**
       * @var \Magebees\Productfeed\Model\Templates
       */
    protected $_proceed_next=true;
    protected $_templates;
    protected $_productObj;
    protected $_om;
    protected $_attributes;
    protected $_product_data_collection=[];
    protected $cws_csv_feed_header = [];
    protected $_objectManager;
    protected $scopeConfig;
    protected $xmlfield;
    protected $xml_field_tree;
    protected $xmlfieldcodevalue;
    protected $xml_rows_elements;
    protected $xml_header;
    protected $frist_page;
    protected $main_file_name;
    protected $feedname;
    protected $test;
    protected $_feed;
    protected $checking = [];
    private $_Storemanager;
    private $_dateformat;
    private $_timeformat;
    private $feedformat;
    protected $_systemStore;
    protected $store_Id;
    protected $field_format;
    protected $_priceCurrency;
    private static $xml = null;
    private static $encoding = 'UTF-8';
    protected $_dynamicattributesResource;
    protected $_filesystem;
    protected $_productCollectionFactory;
    protected $visibility;
	protected $messageManager;
    
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magebees\Productfeed\Model\Feed $feed,
        \Magento\Framework\Filesystem $filesystem,
        \Magebees\Productfeed\Model\Templates $templates,
        \Magento\Store\Model\StoreManagerInterface $Storemanager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Product $productObj,
        \Magebees\Productfeed\Model\ResourceModel\Dynamicattributes $dynamicattributesResource,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\Catalog\Helper\Product $catalogProductHelper,
		\Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_Storemanager = $Storemanager;
        $this->_systemStore = $Storemanager;
        $this->scopeConfig = $scopeConfig;
        $this->_filesystem = $filesystem;
        $this->_priceCurrency = $priceCurrency;
        $this->_dynamicattributesResource = $dynamicattributesResource;
        $this->_feed = $feed;
        $this->_productObj = $productObj;
        $this->_templates = $templates;
        $this->visibility = $visibility;
        $this->_productCollectionFactory = $_productCollectionFactory;
		$this->messageManager = $messageManager;
    }
    
    
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true)
    {
        self::$xml = new DOMDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
        self::$encoding = $encoding;
    }
    public static function &createArray($input_xml)
    {
        
        
        $xml = self::getXMLRoot();
        if (is_string($input_xml)) {
            $parsed = $xml->loadXML($input_xml);
            if (!$parsed) {
                throw new \Exception('[XML2Array] Error parsing the XML string.');
            }
        } else {
            if (get_class($input_xml) != 'DOMDocument') {
                throw new \Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
            }
            $xml = self::$xml = $input_xml;
        }
        $array[$xml->documentElement->tagName] = self::convert($xml->documentElement);
        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $array;
    }
 
    /**
     * Convert an Array to XML
     * @param mixed $node - XML as a string or as an object of DOMDocument
     * @return mixed
     */
    private static function &convert($node)
    {
        $output = [];
 
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
                $output['@cdata'] = trim($node->textContent);
                break;
 
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
 
            case XML_ELEMENT_NODE:
                // for each child node, call the covert function recursively
                for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = self::convert($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;
 
                        // assume more nodes of same kind are coming
                        if (!isset($output[$t])) {
                            $output[$t] = [];
                        }
                        $output[$t][] = $v;
                    } else {
                        //check if it is not an empty text node
                        if ($v !== '') {
                            $output = $v;
                        }
                    }
                }
 
                if (is_array($output)) {
                    // if only one node of its kind, assign it directly instead if array($value);
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v)==1) {
                            $output[$t] = $v[0];
                        }
                    }
                    if (empty($output)) {
                        //for empty nodes
                        $output = '';
                    }
                }
 
                // loop through the attributes and collect them
                if ($node->attributes->length) {
                    $a = [];
                    foreach ($node->attributes as $attrName => $attrNode) {
                        $a[$attrName] = (string) $attrNode->value;
                    }
                    // if its an leaf node, store the value in @value instead of directly storing it.
                    if (!is_array($output)) {
                        $output = ['@value' => $output];
                    }
                    $output['@attributes'] = $a;
                }
                break;
        }
        return $output;
    }
 
    /*
     * Get the root XML node, if there isn't one, create it.
     */
    private static function getXMLRoot()
    {
        if (empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }
    public function getXMLFeedFile($page, $store_export_id, $timestamp, $feed_id, $filename)
    {
        
        //$last_page = $this->unparseXml($page, $store_export_id, $feed_id, $timestamp);
		$last_page = $this->unparseXml($store_export_id, $feed_id, $timestamp,$page);
        
        if ($last_page==$page) {
            $this->_proceed_next=false;
        }
        return $this->exportXML($page, $timestamp, $feed_id, $store_export_id);
    }
    public function parseXMLElements($xml_item_content, $Pkey)
    {
    
    
        $element_key = ['type','value','format','length','parent','cdata'];
        $re = '/(["\'])(?:(?=(\\\\?))\2.)*?\1/';
        foreach ($xml_item_content as $key => $xml_value) :
            if (isset($xml_value['@cdata'])) {
                $value = $xml_value['@cdata'];
                $element_key = ['type','value','format','length','parent','cdata'];
            } else {
                $value = $xml_value;
                $element_key = ['type','value','format','length','parent'];
            }
            if (!is_array($value)) {
                preg_match_all($re, $value, $matches);
        
        
                if (!empty($matches[0])) {
                        /* If Cdata available then add 1 */
                    if (isset($xml_value['@cdata'])) {
                        array_push($matches[0], '1');
                    }
            
                        $xml_element_matches = array_combine($element_key, $matches[0]);
                    if ($Pkey!=null) {
                        if (isset($this->xmlcontent[$key])) {
                            $this->xmlcontent[$Pkey][$key] = $xml_element_matches;
                        } else {
                            $this->xmlcontent[$key] = $xml_element_matches;
                        }
                    } else {
                        $this->xmlcontent[$key]= $xml_element_matches;
                    }
                } else {
                    /* If Cdata available then add 1 */
                    if (isset($xml_value['@cdata'])) {
                        $xml_element_matches = ['static_value'=>$value,'cdata'=>"1"];
                    } else {
                        $xml_element_matches = ['static_value'=>$value];
                    }
        
            
                    if (isset($this->xmlcontent[$key])) {
                        if ($Pkey!=null) {
                            $this->xmlcontent[$Pkey][$key]= $xml_element_matches;
                        } else {
                            $this->xmlcontent[$key] = $xml_element_matches;
                        }
                    } else {
                        $this->xmlcontent[$key] = $xml_element_matches;
                    }
                }
            } else {
                $this->xmlcontent['start-'.$key] = $key;
                
                $this->parseXMLElements($value, $key);
                $this->xmlcontent['end-'.$key] = $key;
            }
        endforeach;
        return $this->xmlcontent;
    }
    
    public function unparseXml($store_export_id, $feed_id, $timestamp,$page = 1)
    {
    
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    
        if ($feed_id!="") {
            $this->_feed->load($feed_id);
            $this->main_file_name = str_replace(' ', '_', $this->_feed->getName());
            $store_id = $this->_feed->getStoreview();
            $conditions_serialized = $this->_feed->getConditionsSerialized();
            $format_serialized = $this->_feed->getFormatSerialized();
            $feed_data = $this->_feed->getData();
            if ($this->_feed->isJSON($conditions_serialized)) {
                $conditions = json_decode($conditions_serialized, true);
            }
    
            if ($this->_feed->isJSON($format_serialized)) {
                $this->field_format = json_decode($format_serialized, true);
            }
    
            $this->_dateformat = str_replace('"', "", $this->field_format['dateformat']);
            $this->_timeformat = str_replace('"', "", $this->field_format['timeformat']);
    
    
    
            $xml_content = $this->_feed->getContent();
            $download_url_info = $this->_feed->getDownloadUrl();
            if ($this->isJSON($download_url_info)) {
                $download_url_info = json_decode($download_url_info, true);
                if (isset($download_url_info['feedname'])) {
                    $this->feedname = $download_url_info['feedname'];
                } else {
                    $this->feedname = $this->_feed->getName().".".$this->_feed->getType();
                }
            }
			$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(isset($scope_info['batch_size']) && $scope_info['batch_size'] > 0)
			{
			$batch_size = $scope_info['batch_size'];
			}else{
			$batch_size = 200;
			}
			
            if ($conditions['attributeset']!="*") {
                $attr_id = $conditions['attributeset'];
                $_product_collection = $this->_productCollectionFactory->create();
                $_product_collection->addAttributeToSelect("*");
                $_product_collection->addFieldToFilter('attribute_set_id', $attr_id);
                $_product_collection->setPageSize($batch_size);
                $_product_collection->setCurPage($page);
            } elseif ($conditions['attributeset']=="*") {
                $_product_collection = $this->_productCollectionFactory->create();
                $_product_collection->addAttributeToSelect("*");
                $_product_collection->setPageSize($batch_size);
                $_product_collection->setCurPage($page);
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
            if ($page==1) {
                $this->frist_page=true;
            } else {
                $this->frist_page=false;
            }
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
                $xml_content = $this->_feed->getContent();
                if ($this->_feed->isJSON($xml_content)) {
                    $xml_content = json_decode($xml_content, true);
                }
        
            
                    $filename = $this->_feed->getName().".xml";
            
            
            
                    $extvardir = $this->_filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
                    //$exportdir = '/export';
					$exportdir = $file_path;
                    $extvardir->create($exportdir);
                    $extvardir->changePermissions($exportdir, \Magento\Framework\Filesystem\DriverInterface::WRITEABLE_DIRECTORY_MODE);
                    $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
                    //$path = $reader->getAbsolutePath('export/');
					$path = $reader->getAbsolutePath($exportdir);
				
            
            
                    $this->_om  = \Magento\Framework\App\ObjectManager::getInstance();
                    $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $Xmlparser = $this->_objectManager->create('Magebees\Productfeed\Model\Xmlparser');
                    $xml_to_array = $this->_objectManager->create('Magebees\Productfeed\Model\XmlToArray');
                    $array_to_xml = $this->_objectManager->create('Magebees\Productfeed\Model\ArrayToXml');
                    $array_to_xmlbuild = $this->_objectManager->create('Magebees\Productfeed\Model\ArrayToXmlbuild');
                    $xml_contentfilename = $this->_feed->getName()."-content.xml";
            
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
                    $store_id = $feed_data['storeview'];
                    //$_product_collection->setStore($store_id);
                foreach ($_product_collection as $product_info) {
                    $product = $product_info->setStoreId($store_id);
                    $this->xmlfield = null;
                    $xml_array = $xml_to_array->createArray($xmldata, $this->feed_xml_item, $product, $feed_data);

                    $xml_item_content = $Xmlparser->find_xml_item($xml_array, $this->feed_xml_item);
                
                    $xml_item_content = $Xmlparser->find_xml_item($xml_array, $this->feed_xml_item);
                    $xml =  $array_to_xml->createXML($this->feed_xml_item, $xml_item_content);
                    $product_xml = str_replace('<?xml version="1.0"?>', '', $xml->saveXML());
                 
    
                    array_push($this->_product_data_collection, $product_xml);
                }
                    return $_product_collection->clear()->getLastPageNumber();
            } else {
                $this->messageManager->addError(__('There is no any products found'));
                return;
            }
        } else {
            $this->messageManager->addError(__('There is no any feed found'));
            return;
        }
    }
    
    
    
    public function exportXML($page, $timestamp, $feed_id, $store_id)
    {
        $current_time = $timestamp;
		$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
		
        $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        //$f_name= 'export-feed-'.$current_time.'.xml';
        $f_name= $this->main_file_name.$current_time.'.xml';
        
        //$f_name= $filename;
        $file_name = $reader->getAbsolutePath($file_path.$f_name);
        $temp_file = $file_name.'-tmp-'.$page;
        $fp = fopen($temp_file, 'w');
            
        if ($page!=1) {
            $product_xml_file = fopen($temp_file, "a");
        } else {
            $product_xml_file = fopen($temp_file, "w");
            
            $codes = ['{{StoreManager.baseurl}}',
            '{{StoreManager.basesecureurl}}',
            '{{StoreManager.frontname}}',
            '{{StoreManager.formattedaddress}}',
            '{{StoreManager.basecurrencycode}}',
            '{{StoreManager.defaultcurrencycode}}',
            '{{StoreManager.currentcurrencycode}}','{{date}}','{{time}}'
            ];
			$formated_date = null;
            $formated_time = null;
            if(isset($this->_dateformat))
            {
			$formated_date = date($this->_dateformat);
            }
            if(isset($this->_timeformat))
            {
				$time=time();
				
				$formated_time = date($this->_timeformat,$time);
			
            }
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

			//Load product by product id
			$feed = $objectManager->create('Magebees\Productfeed\Model\Feed')->load($feed_id);
			$store_id = $feed->getStoreview();
			
            $accurate = [$this->_Storemanager->getStore($store_id)->getBaseUrl(),
            $this->_Storemanager->getStore($store_id)->getBaseSecureUrl(),
            $this->_Storemanager->getStore($store_id)->getFrontendName(),
            $this->_Storemanager->getStore($store_id)->getFormattedAddress(),
            $this->_Storemanager->getStore($store_id)->getBaseCurrencyCode(),
            $this->_Storemanager->getStore($store_id)->getDefaultCurrencyCode(),
            $this->_Storemanager->getStore($store_id)->getCurrentCurrencyCode(),
            $formated_date,
            $formated_time
            ];
           
            $repalced_header =  str_replace($codes, $accurate, $this->xml_header);
            
            
                file_put_contents($temp_file, $repalced_header);
            //file_put_contents($temp_file,$this->xml_header);
        }
        /*
		fwrite($order_xml_file, $this->order2xml($this->_product_data_collection));			
		fclose($order_xml_file);
		*/
        
        foreach ($this->_product_data_collection as $product) {
            file_put_contents($temp_file, $product, FILE_APPEND);
            //fwrite($product_xml_file,$product);
        }
        if ($this->_proceed_next==0) {
            file_put_contents($temp_file, $this->xml_footer, FILE_APPEND);
        }
        fclose($product_xml_file);
        $page++;
        return ["filename"=>$f_name,"feedname"=>$this->feedname,'timestamp'=>$current_time,'fullpath'=>$file_name,"proceed_next"=>$this->_proceed_next,'page'=>$page,"exportedProducts"=>count($this->_product_data_collection)];
    }
    
    public function getXMLkeyvalue($elementAttributes, $product, $elementTitle)
    {
            
        $previous_title = $elementTitle;
        if (is_array($elementAttributes)) {
            foreach ($elementAttributes as $Title => $value) :
                if (is_array($value) and (array_key_exists("type", $value) and
                array_key_exists("value", $value) and
                array_key_exists("format", $value) and
                array_key_exists("length", $value) and
                array_key_exists("parent", $value))) {
                    $this->getXmlFieldValue($product, $Title, $value, $previous_title);
                } elseif (is_array($value) and (array_key_exists("static_value", $value))) {
                    $this->xmlfield[$Title] = $value['static_value'];
                } else {
                    $this->getXMLkeyvalue($value, $product, $Title);
                }
            endforeach;
        } else {
            $this->xmlfield[$previous_title] = $elementAttributes;
        }
    }
    public function getXmlFieldValue($product, $elementTitle, $elementAttributes, $parent_title)
    {
            $this->_om  = \Magento\Framework\App\ObjectManager::getInstance();
            $type = null;
                $attr_value = null;
                $format = null;
                $length = null;
                $parent = null;
        if (array_key_exists("type", $elementAttributes) and
                array_key_exists("value", $elementAttributes) and
                array_key_exists("format", $elementAttributes) and
                array_key_exists("length", $elementAttributes) and
                array_key_exists("parent", $elementAttributes)
                ) {
                    $type = trim($elementAttributes['type'], '"');
                    $attr_value = trim($elementAttributes['value'], '"');
                    $format = trim($elementAttributes['format'], '"');
                    $length = trim($elementAttributes['length'], '"');
                    $parent = trim($elementAttributes['parent'], '"');
                    $parentId="";
            if ($parent=="yes") {
                if ($product->getTypeId()=="simple") {
                }
                {
                $product_id = $product->getId();
                $parentIds = $this->_om->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')
                ->getParentIdsByChild($product_id);
                if (isset($parentIds)) {
                            $parentId = array_shift($parentIds);
                } else {
                    $parentId = "";
                }
                                
                        
                }
                if ($parentId!="") {
                                $parent_product = $this->_om->create('Magento\Catalog\Model\Product')->load($parentId);
                } else {
                    $parent_product = $product;
                    //self::$_Product->load($product_id);
                }
                            
                if (isset($this->xmlfield[$elementTitle]) && ($parent_title!=null)) {
                    $this->xmlfield[$parent_title][$elementTitle] = $this->xmlFieldValue($type, $attr_value, $format, $length, $parent_product, $feed_data);
                } else {
                    $this->xmlfield[$elementTitle] = $this->xmlFieldValue($type, $attr_value, $format, $length, $parent_product, $feed_data);
                }
            } else {
                if (isset($this->xmlfield[$elementTitle]) && ($parent_title!=null)) {
                    $this->xmlfield[$parent_title][$elementTitle] = $this->xmlFieldValue($type, $attr_value, $format, $length, $product, $feed_data);
                } else {
                    $this->xmlfield[$elementTitle] = $this->xmlFieldValue($type, $attr_value, $format, $length, $product, $feed_data);
                }
            }
        } elseif (isset($elementAttributes['static_value']) && ($elementAttributes['static_value']!="")) {
            if (isset($this->xmlfield[$elementTitle]) && ($parent_title!=null)) {
                $this->xmlfield[$parent_title][$elementTitle] = $elementAttributes['static_value'];
            } else {
                $this->xmlfield[$elementTitle] = $elementAttributes['static_value'];
            }
        } else {
            $this->getXMLkeyvalue($elementAttributes, $product, $elementTitle);
        }
            //return $this->xmlfield;
    }
    public function xmlProduct($product, $xml_rows_elements)
    {
        
        $column_count = count(array_filter($xml_rows_elements));
        $xml_item = [];
        $this->xmlfield = null;
        
        
        
        if ($column_count>0) {
            foreach ($xml_rows_elements as $elementTitle => $elementAttributes) :
                if (is_array($elementAttributes)) {
                    $this->getXmlFieldValue($product, $elementTitle, $elementAttributes, $parent_title = null);
                } else {
                    $this->getXMLkeyvalue($elementAttributes, $product, $elementTitle);
                }
            endforeach;
        }
        return $this->xmlfield;
    }
    public function xmlFieldValue($type, $attr_value, $format, $length, $product, $feed_data)
    {
                
        switch ($type) {
            case 'attribute':
                $value = $this->getProductAttributeValue($product, $attr_value, $feed_data);
                if($attr_value=='weight'){
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$weight_unit = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('general/locale/weight_unit');
				$value = $value.$weight_unit;
				if($value > 0){
						$formatted_value =$this->getFormatedvalue($value, $format, $length, $feed_data);
					}else{
						$formatted_value = null;
					}
				}else{
				$formatted_value =$this->getFormatedvalue($value, $format, $length, $feed_data);
				}
				break;
            case 'category':
                $all_cats = $product->getCategoryIds();
                if (count($all_cats)>1) {
                    $cat_id = end($all_cats);
                } elseif (count($all_cats)==1) {
                    $cat_id = $all_cats[0];
                } else {
                    $cat_id = null;
                }
                $mapping_id = $attr_value;
                            
                $value = $this->getCategoryMappingValue($cat_id, $mapping_id);
                $formatted_value =$this->getFormatedvalue($value, $format, $length, $feed_data);
                break;
            case 'text':
                $value = $attr_value;
                $formatted_value =$this->getFormatedvalue($value, $format, $length, $feed_data);
                break;
            default:
                $formatted_value = $attr_value;
        }
                    return $formatted_value;
    }
    public function getFormatedvalue($value, $format, $length, $feed_data)
    {
        
        $format_serialized = $feed_data['format_serialized'];
        if ($this->_feed->isJSON($format_serialized)) {
                $this->field_format = json_decode($format_serialized, true);
        }
    
        $value = ($length > 0 ? substr((string)$value, "0", $length)  : (string)$value);
        switch ($format) {
            case 'strip_tags':
                $formated_value = strip_tags($value);
                break;
            case 'html_escape':
                $formated_value = htmlspecialchars($value);
                break;
            case 'date':
                $date=date_create($value);
                $formated_value = date_format($date, $this->field_format['dateformat']);
                break;
            case 'price':
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $current_currency = $objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($this->field_format['currency']);
                $currencies_codes = $this->_systemStore->getStore($this->store_Id)->getAvailableCurrencyCodes();
                        
                $from_currency_code = $this->_systemStore->getStore($this->store_Id)->getBaseCurrencyCode();
                $to_currency_code = $currencies_codes[$this->field_format['currency']];
                    
                $currency = $objectManager->create('Magento\Directory\Helper\Data');
                //echo "value::".$value;
                $converted_price = $currency->currencyConvert($value, $from_currency_code, $to_currency_code);
                if ($this->field_format['currencycode']==1) {
                    //$converted_price = $to_currency_code." ".number_format($converted_price, $this->field_format['numberofdecimalpoint']);
					$converted_price = number_format($converted_price, $this->field_format['numberofdecimalpoint'])." ".$to_currency_code;
                } else {
                    $converted_price = $this->_priceCurrency->format(
                        $converted_price,
                        $includeContainer = false,
                        $this->field_format['numberofdecimalpoint'],
                        $scope = null,
                        $to_currency_code
                    );
                }
                            

                //it return price according to current store from base currency
                $formated_value = ($length > 0 ? substr($converted_price, "0", $length)  : $converted_price);
                            
                break;
            case 'integer':
                $formated_value = ($length > 0 ? substr(intval($value), "0", $length)  : intval($value));
                break;
            case 'lowercase':
                $formated_value = ($length > 0 ? substr(strtolower($value), "0", $length)  : strtolower($value));
                break;
            case 'uppercase ':
                $formated_value = ($length > 0 ? substr(strtoupper($value), "0", $length)  : strtoupper($value));
                break;
            case 'capitalize ':
                $formated_value = ($length > 0 ? substr(ucfirst($value), "0", $length)  : ucfirst($value));
                break;
            default:
                $formated_value = $value;
                return $formated_value;
                            break;
        }
            return $formated_value;
    }
    public function getCategoryMappingValue($cat_id, $mapping_id)
    {
        $this->_om  = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_mapping = $this->_om->create('Magebees\Productfeed\Model\Mapping')->load($mapping_id);
        $mapping_content = $this->_mapping->getMappingcontent();
        if ($this->_mapping->isJSON($mapping_content)) {
            $mapping_content_arr = json_decode($mapping_content, true);
        }
        if (isset($mapping_content_arr[$cat_id])) {
            $mapping_value = $mapping_content_arr[$cat_id];
        } else {
            $mapping_value = "";
        }
        return $mapping_value;
    }
    public function getProductAttributeValue($product, $attr_code, $feed_data)
    {
        if ($attr_code=='please-select') {
            return '';
        }
        $this->_om  = \Magento\Framework\App\ObjectManager::getInstance();
        $pro_attr_value = null;
        $attribute = $this->getAttribute($attr_code);
        $pro_info = $product->getData();
        
        unset($pro_info['image']);
        unset($pro_info['small_image']);
        unset($pro_info['thumbnail']);
        unset($pro_info['swatch_image']);
        unset($pro_info['final_price']);
        unset($pro_info['quantity_and_stock_status']);
        $parent_attribute = "";
        if ((!$attribute) && (empty($pro_info[$attr_code]))) {
            /* Custom Product Information*/
            foreach ($this->_templates->getAttributeList() as $key => $list) :
                foreach ($list as $list_key => $value) :
                    if ($attr_code==$list_key) :
                        $parent_attribute = $key;
                        break;
                    endif;
                endforeach;
            endforeach;
            $pro_attr_value = '';
            switch ($parent_attribute) {
                case 'Primary Attribute':
                    if ($attr_code=="url") {
                        $pro_attr_value = $product->getProductUrl();
                    }
                    break;
                case 'Price Attribute':
                    if ($product->getTypeId()=='bundle') {
                        if ($attr_code=="price_type") {
                            $pro_attr_value = $product->getPriceType() == 0 ? "dynamic" : "fixed";
                        }
                        if ($attr_code=="price_view") {
                            $pro_attr_value = $product->getPriceView() == 0 ? "price range" : "as low as";
                        }
                    }
                    if ($attr_code=="final_price") {
                        $pro_attr_value = $product->getFinalPrice();
                    }
                    if ($attr_code=="price") {
                        $pro_attr_value = $product->getPrice();
                    }
                    if ($attr_code=="special_price") {
                        $pro_attr_value = $product->getSpecialPrice();
                    }
                    break;
                case 'Image Attribute':
                    $store = $this->_om->create('Magento\Store\Model\StoreManagerInterface')->getStore();
                    if ($attr_code=="image") {
                        $pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
                    } elseif ($attr_code=="small_image") {
                        $pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSmallImage();
                    } elseif ($attr_code=="thumbnail") {
                        $pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
                    } elseif ($attr_code=="swatch_image") {
                        $pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSwatchImage();
                    } elseif (($attr_code=="gallery") || (strpos($attr_code, 'image_') === 0)) {
                        $gimages = $this->_om->create('\Magento\Catalog\Model\Product')->load($product->getId())->getMediaGalleryImages();
                        $store = $this->_om->create('Magento\Store\Model\StoreManagerInterface')->getStore();
                        $i=0;
                        $image_count = count($gimages);
                        $pro_attr_value_list = '';
                        if ($image_count>0) {
                            foreach ($gimages as $_image) {
                                if ($_image->getMediaType()=="image") {
                                    if ($attr_code=="gallery") {
                                        $pro_attr_value = $_image->getUrl()."|";
                                        if ($i==0) {
                                            $i++;
                                            //$pro_attr_value = $_image->getUrl()."|";
                                            $pro_attr_value_list = $_image->getUrl();
                                        } else {
                                            //$pro_attr_value .= $_image->getUrl();
                                            $pro_attr_value_list .= $_image->getUrl();
                                        }
                                    }
                                    if ("image_".$_image->getPosition()==$attr_code) {
                                        $pro_attr_value = $_image->getUrl();
                                        break;
                                    }
                                }
                            }
                        }
                                
                        if ($attr_code=="gallery") {
                            $pro_attr_value = $pro_attr_value_list;
                        }
                    }
                    break;
                case 'Stock Qty Attribute':
                    /* Stock Code */
                    $stockRegistry = $this->_om->create('Magento\CatalogInventory\Api\StockRegistryInterface');
                    $stockItem = $stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
                    /* End of Stock Code */
                    if ($attr_code=="is_in_stock") {
                        if ($stockItem) {
                            $pro_attr_value = $stockItem->getIsInStock();
                            break;
                        } else {
                            $pro_attr_value = "";
                            break;
                        }
                    }
                    if ($attr_code=="quantity_and_stock_status") {
                        if ($stockItem) {
                            $pro_attr_value = $stockItem->getQty();
                            break;
                        } else {
                            $pro_attr_value = "";
                            break;
                        }
                    }
					break;
                case 'Category Attribute':
                    $store_Id = $feed_data['storeview'];
                    $category_model = $this->_om->create('\Magento\Catalog\Model\Category');
                    $all_cats = $product->getCategoryIds();
                    $pro_attr_value = "";
                    if (count($all_cats)>0) {
                        if ($attr_code=="category.entity_id") {
                            $pro_attr_value = end($all_cats);
                        }
                        if ($attr_code=="category") {
                            $cat_id = end($all_cats);
                                    
                                    
                            $category = $category_model->setStoreId($store_Id)->load($cat_id);
                            $pro_attr_value = $category->getName();
                        }
                        if ($attr_code=="category.url") {
                            $cat_id = end($all_cats);
                            $category = $category_model->setStoreId($store_Id)->load($cat_id);
                            $pro_attr_value = $category->getUrl();
                        }
                        if ($attr_code=="category.path") {
                        /* Category Code */
                            $main_cnt = count($all_cats);
                            $cat_str_main = '';
                            $j = 0;
                            foreach ($all_cats as $ac) {
                                $cat_str = "";
                                $category = $category_model->load($ac);
                                $pathIds = explode("/", $category->getPath());
                                foreach ($pathIds as $catid) {
                                    $category_sub = $category_model->setStoreId($store_Id)->load($catid);
                                    if ($category_sub->getLevel() == 0) {
                                        continue;
                                    }
                                    if ($category_sub->getLevel() == 1) {
                                        //$cat_str = $category_sub->getName();
                                        continue;
                                    }
                                    if ($category_sub->getLevel() == 2) {
                                        $cat_str = $category_sub->getName();
                                    } elseif ($category_sub->getLevel() > 2) {
                                        $cat_str = $cat_str."/".$category_sub->getName();
                                    }
                                }
                                if ($j < 1) {
                                    $cat_str_main = $cat_str;
                                } else {
                                    $cat_str_main = trim($cat_str_main .",".$cat_str);
                                }
                                $j = $j+1;
                            }
                            $cat_str_main = ltrim($cat_str_main, ",");
                            $pro_attr_value = $cat_str_main;
                        }
                    } else {
                        $pro_attr_value = "";
                    }
                    break;
                case 'Dynamic Attributes':
                    $conditions_attribute = $this->_dynamicattributesResource->getConditionByCode($attr_code);
					
                    foreach ($conditions_attribute as $orkey => $orValues) :
                        $or_result = $orValues['result']['value'];
                        $attributes = $orValues['attribute'];
                        $operators = $orValues['operator'];
                        $check_values = $orValues['value'];
                        $result = [];
                        foreach (array_keys($attributes) as $key => $value) :
                            $result[] = $this->checkProductValue($product, $attributes[$value][0], $operators[$value][0], $check_values[$value][0]);
                        endforeach;
					$result = array_filter($result);
					    if (count(array_unique($result)) === 1) {
                            return $pro_attr_value = $or_result;
                            break;
                        }
                    endforeach;
                    $pro_attr_value = "";
                    break;
                default:
                    $pro_attr_value = "";
            }
            return $pro_attr_value;
        }
        
        $pro_attr_value = $product->getData($attr_code);
        
        if ($attribute->usesSource()) {
            $option = $attribute->getSource()->getOptionText($pro_attr_value);
            if ($pro_attr_value && empty($option) && $option != '0') {
                $this->addException(
                    $this->messageManager->addError(__('Invalid option ID specified for %1 (%2), skipping the record.', $attr_code, $pro_attr_value))
                );
                return '';
            }
            if (is_array($option)) {
                $pro_attr_value = join(",", $option);
            } elseif (is_object($option)) {
                $pro_attr_value = $option->getText();
            } else {
                $pro_attr_value = $option;
            }
            unset($option);
        } elseif (is_array($pro_attr_value)) {
            $pro_attr_value = join(",", $option);
        }
        
        
        return $pro_attr_value;
    }
    public function checkProductValue($product, $attribute_code, $operator, $Check_value)
    {
        
        if ($attribute_code=='please-select') {
            return '';
        }
        $pro_attr_value = null;
        $attribute = $this->getAttribute($attribute_code);
        $pro_info = $product->getData();
        
        unset($pro_info['image']);
        unset($pro_info['small_image']);
        unset($pro_info['thumbnail']);
        unset($pro_info['swatch_image']);
        unset($pro_info['final_price']);
        unset($pro_info['quantity_and_stock_status']);
        
        if ((!$attribute) && (empty($pro_info[$attribute_code]))) {
            /* Custom Product Information*/
            foreach ($this->_templates->getAttributeList() as $key => $list) :
                foreach ($list as $list_key => $value) :
                    if ($attribute_code==$list_key) :
                        $parent_attribute = $key;
                        break;
                    endif;
                endforeach;
            endforeach;
            $pro_attr_value = '';
            switch ($parent_attribute) {
                case 'Primary Attribute':
                    if ($attribute_code=="url") {
                        $pro_attr_value = $product->getProductUrl();
                    }
                    break;
                case 'Price Attribute':
                    if ($product->getTypeId()=='bundle') {
                        if ($attribute_code=="price_type") {
                            $pro_attr_value = $product->getPriceType() == 0 ? "dynamic" : "fixed";
                        }
                        if ($attribute_code=="price_view") {
                            $pro_attr_value = $product->getPriceView() == 0 ? "price range" : "as low as";
                        }
                    }
                    if ($attribute_code=="final_price") {
                        $pro_attr_value = $product->getFinalPrice();
                    }
                    if ($attribute_code=="price") {
                        $pro_attr_value = $product->getPrice();
                    }
                    if ($attribute_code=="special_price") {
                        $pro_attr_value = $product->getSpecialPrice();
                    }
                    break;
                case 'Image Attribute':
                    $store = $this->_om->create('Magento\Store\Model\StoreManagerInterface')->getStore();
                    if ($attribute_code=="image") {
                        $pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
                    } elseif ($attribute_code=="small_image") {
                        $pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSmallImage();
                    } elseif ($attribute_code=="thumbnail") {
                        $pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
                    } elseif ($attribute_code=="swatch_image") {
                        $pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSwatchImage();
                    } elseif (($attribute_code=="gallery") || (strpos($attribute_code, 'image_') === 0)) {
                        $gimages = $this->_om->create('\Magento\Catalog\Model\Product')->load($product->getId())->getMediaGalleryImages();
                        $store = $this->_om->create('Magento\Store\Model\StoreManagerInterface')->getStore();
                        $i=0;
                        $image_count = count($gimages);
                        $pro_attr_value_list = '';
                        if ($image_count>0) {
                            foreach ($gimages as $_image) {
                                if ($_image->getMediaType()=="image") {
                                    if ($attribute_code=="gallery") {
                                        $pro_attr_value = $_image->getUrl()."|";
                                        if ($i==0) {
                                            $i++;
                                            //$pro_attr_value = $_image->getUrl()."|";
                                            $pro_attr_value_list = $_image->getUrl();
                                        } else {
                                            //$pro_attr_value .= $_image->getUrl();
                                            $pro_attr_value_list .= $_image->getUrl();
                                        }
                                    }
                                    if ("image_".$_image->getPosition()==$attribute_code) {
                                        $pro_attr_value = $_image->getUrl();
                                        break;
                                    }
                                }
                            }
                        }
                        if ($attribute_code=="gallery") {
                            $pro_attr_value = $pro_attr_value_list;
                        }
                    }
                    break;
                case 'Stock Qty Attribute':
                    /* Stock Code */
                    $stockRegistry = $this->_om->create('Magento\CatalogInventory\Api\StockRegistryInterface');
                    $stockItem = $stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
                    /* End of Stock Code */
                    if ($attribute_code=="is_in_stock") {
                        if ($stockItem) {
                            $pro_attr_value = $stockItem->getIsInStock();
                            break;
                        } else {
                            $pro_attr_value = "";
                            break;
                        }
                    }
                    if ($attribute_code=="quantity_and_stock_status") {
                        if ($stockItem) {
                            $pro_attr_value = $stockItem->getQty();
                            break;
                        } else {
                            $pro_attr_value = "";
                            break;
                        }
                    }
					break;
                case 'Category Attribute':
                    $category_model = $this->_om->create('\Magento\Catalog\Model\Category');
                    $all_cats = $product->getCategoryIds();
                    $pro_attr_value = "";
                    if (count($all_cats)>0) {
                        if ($attribute_code=="category.entity_id") {
                            $pro_attr_value = end($all_cats);
                        }
                        if ($attribute_code=="category") {
                            $cat_id = end($all_cats);
                            $category = $category_model->setStoreId("1")->load($cat_id);
                            $pro_attr_value = $category->getName();
                        }
                        if ($attribute_code=="category.url") {
                            $cat_id = end($all_cats);
                            $category = $category_model->setStoreId("1")->load($cat_id);
                            $pro_attr_value = $category->getUrl();
                        }
                        if ($attribute_code=="category.path") {
                        /* Category Code */
                            $main_cnt = count($all_cats);
                            $cat_str_main = '';
                            $j = 0;
                            foreach ($all_cats as $ac) {
                                $cat_str = "";
                                $category = $category_model->load($ac);
                                $pathIds = explode("/", $category->getPath());
                                foreach ($pathIds as $catid) {
                                    $category_sub = $category_model->setStoreId("1")->load($catid);
                                    if ($category_sub->getLevel() == 0) {
                                        continue;
                                    }
                                    if ($category_sub->getLevel() == 1) {
                                        //$cat_str = $category_sub->getName();
                                        continue;
                                    }
                                    if ($category_sub->getLevel() == 2) {
                                        $cat_str = $category_sub->getName();
                                    } elseif ($category_sub->getLevel() > 2) {
                                        $cat_str = $cat_str."/".$category_sub->getName();
                                    }
                                }
                                if ($j < 1) {
                                    $cat_str_main = $cat_str;
                                } else {
                                    $cat_str_main = trim($cat_str_main .",".$cat_str);
                                }
                                $j = $j+1;
                            }
                            $cat_str_main = ltrim($cat_str_main, ",");
                            $pro_attr_value = $cat_str_main;
                        }
                    } else {
                        $pro_attr_value = "";
                    }
                    break;
                default:
                    $pro_attr_value = "";
            }
            if ($operator=="==") {
                if ($Check_value==$pro_attr_value) {
                    return 1;
                }
            } elseif ($operator=="==") {
                if ($pro_attr_value==$Check_value) {
                    return 1;
                }
            } elseif ($operator=="!=") {
                if ($pro_attr_value!=$Check_value) {
                    return 1;
                }
            } elseif ($operator==">") {
                if ($pro_attr_value > $Check_value) {
                    return 1;
                }
            } elseif ($operator==">=") {
                if ($pro_attr_value >= $Check_value) {
                    return 1;
                }
            } elseif ($operator=="<") {
                if ($pro_attr_value < $Check_value) {
                    return 1;
                }
            } elseif ($operator=="<=") {
                if ($pro_attr_value <= $Check_value) {
                    return 1;
                }
            } elseif ($operator=="{}") {
                if (is_array($pro_attr_value)) {
                    if (in_array($Check_value, $pro_attr_value)) {
                        return 1;
                    }
                } elseif (is_object($pro_attr_value)) {
                    $pro_attr_value = $pro_attr_value->getText();
                    if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false) {
                        return 1;
                    }
                } else {
                    if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false) {
                        return 1;
                    }
                }
            } elseif ($operator=="!{}") {
                if (is_array($pro_attr_value)) {
                    if (!in_array($Check_value, $pro_attr_value)) {
                        return 1;
                    }
                } elseif (is_object($pro_attr_value)) {
                    $pro_attr_value = $pro_attr_value->getText();
                    if (strpos($pro_attr_value, '"'.$Check_value.'"') === false) {
                        return 1;
                    }
                } else {
                    if (strpos($pro_attr_value, '"'.$Check_value.'"') === false) {
                        return 1;
                    }
                }
            } elseif ($operator=="()") {
                if (is_array($pro_attr_value)) {
                    if (in_array($Check_value, $pro_attr_value)) {
                        return 1;
                    }
                } elseif (is_object($pro_attr_value)) {
                    $pro_attr_value = $pro_attr_value->getText();
                    if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false) {
                        return 1;
                    }
                } else {
                    if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false) {
                        return 1;
                    }
                }
            } elseif ($operator=="!()") {
                if (is_array($pro_attr_value)) {
                    if (!in_array($Check_value, $pro_attr_value)) {
                        return 1;
                    }
                } elseif (is_object($pro_attr_value)) {
                    $pro_attr_value = $pro_attr_value->getText();
                    if (strpos($pro_attr_value, '"'.$Check_value.'"') === false) {
                        return 1;
                    }
                } else {
                    if (strpos($pro_attr_value, '"'.$Check_value.'"') === false) {
                        return 1;
                    }
                }
            }
                    return 0;
        }
        
        $pro_attr_value = $product->getData($attribute_code);
        
        if ($attribute->usesSource()) {
            $option = $attribute->getSource()->getOptionText($pro_attr_value);
            if ($pro_attr_value && empty($option) && $option != '0') {
                $this->addException(
                    $this->messageManager->addError(__('Invalid option ID specified for %1 (%2), skipping the record.', $attribute_code, $pro_attr_value))
                );
                return '';
            }
            if (is_array($option)) {
                $pro_attr_value = join(",", $option);
            } elseif (is_object($option)) {
                $pro_attr_value = $option->getText();
            } else {
                $pro_attr_value = $option;
            }
            unset($option);
        } elseif (is_array($pro_attr_value)) {
            $pro_attr_value = join(",", $option);
        }
        if ($operator=="==") {
            if ($Check_value==$pro_attr_value) {
                return 1;
            }
        } elseif ($operator=="==") {
            if ($pro_attr_value==$Check_value) {
                return 1;
            }
        } elseif ($operator=="!=") {
            if ($pro_attr_value!=$Check_value) {
                return 1;
            }
        } elseif ($operator==">") {
            if ($pro_attr_value > $Check_value) {
                return 1;
            }
        } elseif ($operator==">=") {
            if ($pro_attr_value >= $Check_value) {
                return 1;
            }
        } elseif ($operator=="<") {
            if ($pro_attr_value < $Check_value) {
                return 1;
            }
        } elseif ($operator=="<=") {
            if ($pro_attr_value <= $Check_value) {
                return 1;
            }
        } elseif ($operator=="{}") {
            if (is_array($pro_attr_value)) {
                if (in_array($Check_value, $pro_attr_value)) {
                    return 1;
                }
            } elseif (is_object($pro_attr_value)) {
                $pro_attr_value = $pro_attr_value->getText();
                if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false) {
                    return 1;
                }
            } else {
                if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false) {
                    return 1;
                }
            }
        } elseif ($operator=="!{}") {
            if (is_array($pro_attr_value)) {
                if (!in_array($Check_value, $pro_attr_value)) {
                    return 1;
                }
            } elseif (is_object($pro_attr_value)) {
                $pro_attr_value = $pro_attr_value->getText();
                if (strpos($pro_attr_value, '"'.$Check_value.'"') === false) {
                    return 1;
                }
            } else {
                if (strpos($pro_attr_value, '"'.$Check_value.'"') === false) {
                    return 1;
                }
            }
        } elseif ($operator=="()") {
            if (is_array($pro_attr_value)) {
                if (in_array($Check_value, $pro_attr_value)) {
                    return 1;
                }
            } elseif (is_object($pro_attr_value)) {
                $pro_attr_value = $pro_attr_value->getText();
                if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false) {
                    return 1;
                }
            } else {
                if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false) {
                    return 1;
                }
            }
        } elseif ($operator=="!()") {
            if (is_array($pro_attr_value)) {
                if (!in_array($Check_value, $pro_attr_value)) {
                    return 1;
                }
            } elseif (is_object($pro_attr_value)) {
                $pro_attr_value = $pro_attr_value->getText();
                if (strpos($pro_attr_value, '"'.$Check_value.'"') === false) {
                    return 1;
                }
            } else {
                if (strpos($pro_attr_value, '"'.$Check_value.'"') === false) {
                    return 1;
                }
            }
        }
                    return 0;
    }
    public function getAttribute($code)
    {
        $image_tag = preg_match("#^image_(.*)$#i", $code);
        if (($code!="tier_price") && ($code!="image") && ($code!="small_image") && ($code!="thumbnail") && ($code!="swatch_image") && ($image_tag!="1") && ($code!="gallery") && ($code!="quantity_and_stock_status") && ($code!="category.entity_id") && ($code!="category.url")) {
            if (!isset($this->_attributes[$code])) {
                $this->_attributes[$code] = $this->_productObj->getResource()->getAttribute($code);
            }
            return $this->_attributes[$code];
        } else {
            return '';
        }
    }
    
    public function downloadCsv($file)
    {
        if (file_exists($file)) {
            //set appropriate headers
            header('Content-Description: File Transfer');
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
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
}
