<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Model;
use Magento\Framework\App\Config\ScopeConfigInterface;
class Csvexport extends \Magento\Framework\Model\AbstractModel
{
      /**
     * @var \Magebees\Productfeed\Model\Templates
     */
	 protected $_proceed_next=true;
	  protected $_filesystem;
    protected $_templates;
	protected $_product;
	protected $_om;
	protected $_attributes;
	protected $_product_data_collection=array();
	protected $cws_csv_feed_header = array();
	protected $_objectManager;
	protected $scopeConfig;
	protected $main_file_name;
	protected $feedname;
	protected $_feed;
	protected $_priceCurrency;
	protected $field_format;
	protected $_dynamicattributesResource;
	protected $_systemStore;
	protected $store_id;
	protected $_productCollectionFactory;
	protected $messageManager;
	
    public function __construct(
		ScopeConfigInterface $scopeConfig,
		\Magebees\Productfeed\Model\Feed $feed,
		
		\Magento\Framework\Filesystem $filesystem,
		\Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productCollectionFactory,
		\Magento\Store\Model\StoreManagerInterface $systemStore,
		\Magebees\Productfeed\Model\ResourceModel\Dynamicattributes $dynamicattributesResource,
		\Magento\Framework\Serialize\SerializerInterface $serializer,
		\Magento\Framework\Message\ManagerInterface $messageManager
    ) {
			$this->_feed = $feed;
			$this->_systemStore = $systemStore;
			$this->_priceCurrency = $priceCurrency;
			$this->_filesystem = $filesystem;
			$this->scopeConfig = $scopeConfig;
			$this->_dynamicattributesResource = $dynamicattributesResource;
			$this->_productCollectionFactory = $_productCollectionFactory;
			$this->_serializer = $serializer;
			$this->messageManager = $messageManager;
        
    }
	public function getCSVFeedFile($page,$store_export_id,$timestamp,$feed_id,$filename){
		
		$last_page = $this->unparseCsv($store_export_id,$feed_id,$timestamp,$page);
		
		if($last_page==$page){
			$this->_proceed_next=false;
		}
		return $this->exportCSV($page,$timestamp,$feed_id);
	}
	public function unparseCsv($store_export_id,$feed_id,$timestamp,$page=1)
    {
	$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	
	if($feed_id!=""){
	$om = \Magento\Framework\App\ObjectManager::getInstance();
	
	$this->_feed->load($feed_id);
	
	$conditions_serialized = $this->_feed->getConditionsSerialized();
	if($this->_feed->isJSON($conditions_serialized)){
				$conditions = json_decode($conditions_serialized, true);
	}
	$format_serialized = $this->_feed->getFormatSerialized();
	if($this->_feed->isJSON($format_serialized)){
				$this->field_format = json_decode($format_serialized, true);
	}
	$feed_data = $this->_feed->getData();
	$csv_content = $this->_feed->getContent();
	$this->main_file_name = str_replace(' ', '_', $this->_feed->getName());
	
	if($this->_feed->isJSON($csv_content)){
			$csv_content = json_decode($csv_content, true);
			$get_attr = $csv_content['attr'];
	}else{
		$get_attr = "*";
	}
	//
	$download_url_info = $this->_feed->getDownloadUrl();
	if($this->isJSON($download_url_info)){
		$download_url_info = json_decode($download_url_info, true);
		if(isset($download_url_info['feedname'])){
			$feedname=preg_replace('/\s+/', '', $download_url_info['feedname']);
			$this->feedname = $feedname;
			
		}else{
			$feedname=preg_replace('/\s+/', '', $this->_feed->getName().".".$this->_feed->getType());
			$this->feedname = $feedname;
			//$this->feedname = $this->_feed->getName().".".$this->_feed->getType();

		}
	}
			$this->store_id = $this->_feed->getStoreview();	
			$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(isset($scope_info['batch_size']) && $scope_info['batch_size'] > 0)
			{
			$batch_size = $scope_info['batch_size'];
			}else{
			$batch_size = 200;
			}
			
	if($conditions['attributeset']!="*")
	{
		$attr_id = $conditions['attributeset'];
		$_product_collection = $this->_productCollectionFactory->create();
		$_product_collection->addAttributeToSelect("*");
		$_product_collection->addFieldToFilter('attribute_set_id',$attr_id);
		$_product_collection->setPageSize($batch_size);
		$_product_collection->setCurPage($page);
		
	}else if($conditions['attributeset']=="*"){
		$_product_collection = $this->_productCollectionFactory->create();
		$_product_collection->addAttributeToSelect("*");
		$_product_collection->setPageSize($batch_size);
		$_product_collection->setCurPage($page);
	}
			$_product_collection->clear()
				->addAttributeToFilter('type_id', array('in' => $conditions['producttype']))
				->addAttributeToFilter('visibility', array('in' => $conditions['visibility']))
				->addAttributeToFilter('status', array('in' => $conditions['status']));
			if(isset($conditions['categories'])){
				$categoryFilter = array();
				foreach($conditions['categories'] as $key => $id):
					$categoryFilter['eq'][] = $id;
				endforeach;
				if(count($categoryFilter['eq']) > 0){
					$_product_collection->addCategoriesFilter($categoryFilter);	
					}	
			}
			
			if($conditions['stock']!="0"){
				if($conditions['stock']=="1"){
				$_product_collection = $this->addInStockFilterToCollection($_product_collection);
				}else if($conditions['stock']=="2"){
				$_product_collection = $this->addOutStockFilterToCollection($_product_collection);
				}
			}
			$_product_collection->addStoreFilter($this->store_id);
			$_product_collection->load();	
	
	

	if(count($_product_collection)>0){
		
		$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
		
		$extvardir = $this->_filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
		//$exportdir = '/export';
		$exportdir = $file_path;
		$csv_content = $this->_feed->getContent();
		
		if($this->_feed->isJSON($this->_feed->getFormatSerialized())){
				$this->field_format = json_decode($this->_feed->getFormatSerialized(),true);
		}
		
		
		
		if($this->_feed->isJSON($csv_content)){
			$csv_content = json_decode($csv_content, true);
			if(count($csv_content['name'])>0)
			{
			$this->store_id = $feed_data['storeview'];
			$_product_collection->setStore($this->store_id);
			foreach($_product_collection as $product){
				//print_r(get_class_methods($product));
				
				$row = $this->csvProductRow($product,$csv_content,$this->store_id,$this->field_format);
				
				
				array_push($this->_product_data_collection,$row);
			}
			$current_time = $timestamp;
			
			$reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
			if($page!=1){
				$cws_csv_header = $reader->getAbsolutePath($file_path.'cws_csv_feed_header-'.$current_time.'.obj');
				$header_string_obj=file_get_contents($cws_csv_header);
				$this->cws_csv_feed_header=$this->_serializer->unserialize($header_string_obj);
				
			}else{
				$this->cws_csv_feed_header=array();						
			}
			
			foreach ($this->_product_data_collection as $product) {
				foreach(array_keys($product) as $k=>$v){
					if (!in_array($v, $this->cws_csv_feed_header)) {
						array_push($this->cws_csv_feed_header,$v);
					}
				}				
			}

			
			
			return $_product_collection->getLastPageNumber();
		}else{
			$this->messageManager->addError(
                    __('There are no any content set for the feed'));
			return;
			}
		}
	}else
	{
		$this->messageManager->addError(__('There is no any products found'));
		return;
	}
    }else{
		$this->messageManager->addError(__('There is no any feed found'));
		return;
	} 
	}
	public function exportCSV($page,$timestamp,$feed_id){
		$current_time = $timestamp;
		$extvardir = $this->_filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
		$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
		
		$exportdir = $file_path;
		$extvardir->create($exportdir);
        $extvardir->changePermissions($exportdir, \Magento\Framework\Filesystem\DriverInterface::WRITEABLE_DIRECTORY_MODE);	
		$reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
		
		
			
			$f_name= $this->main_file_name.$current_time.'.csv';
			$file_name = $reader->getAbsolutePath($file_path.$f_name);
			
			
			$fp = fopen($file_name.'-tmp-'.$page, 'w');
			$header=true;
			$temp_order_data=array();
			
			$cws_csv_header = $reader->getAbsolutePath($file_path."cws_csv_feed_header-".$current_time.".obj");
			file_put_contents($cws_csv_header, $this->_serializer->serialize($this->cws_csv_feed_header),LOCK_EX);
			fputcsv($fp, array_values($this->cws_csv_feed_header));
			
			
			
			foreach ($this->_product_data_collection as $product) {
				
				$o_data=array_fill_keys(array_values($this->cws_csv_feed_header), '');
				foreach($product as $o_key=>$o_val){
					if (in_array($o_key, $this->cws_csv_feed_header)) {
						$o_data[$o_key]=$o_val;
					}
				}
				fputcsv($fp, array_values($o_data));
				
				//fputcsv($fp, $product);
			}
			fclose($fp);
			$page++;
			return array("filename"=>$f_name,"feedname"=>$this->feedname,'fullpath'=>$file_name,"proceed_next"=>$this->_proceed_next,'page'=>$page,"exportedProducts"=>count($this->_product_data_collection),'timestamp'=>$current_time);
	}
	
	
	public function csvProductRow($product,$csv_content,$store_id,$field_format){
		
		$column_count = count(array_filter($csv_content['name']));
		$row = array();
		$this->_om = \Magento\Framework\App\ObjectManager::getInstance();
		$this->_templates = $this->_om->create('Magebees\Productfeed\Model\Templates');
		$parentId="";
		if (in_array("yes", $csv_content['parent'])) {
			if($product->getTypeId()=="simple");
			{
				$product_id = $product->getId();	
				$parentIds = $this->_om->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')
							->getParentIdsByChild($product_id);
				if(isset($parentIds)){
									$parentId = array_shift($parentIds);	
								}else{
									$parentId = "";
								}
				
				
			}
			if($parentId!=""){
				$parent_product = $this->_om->create('Magento\Catalog\Model\ProductFactory')->create()->load($parentId); 
				}else{
				$parent_product = $product;
									
			}
		}else{
			unset($parent_product);
		}
		
		if($column_count>0){
			for($i=0;$i<$column_count;$i++){
				if(isset($csv_content['type'][$i]))
				{	
					$type = $csv_content['type'][$i];
					switch($type){
						case 'attribute':
							if($csv_content['parent'][$i]=="yes")
							{
							$value = $this->getProductAttributeValue($parent_product,$csv_content['attr'][$i]);
							}else{
							$value = $this->getProductAttributeValue($product,$csv_content['attr'][$i]);
							}
							$formatted_value =$this->getFormatedvalue($value,$csv_content['format'][$i],$csv_content['length'][$i],$store_id,$field_format);
							$row[$csv_content['name'][$i]] = $formatted_value;
							//array_push($row,$formatted_value);
							break;
						case 'category':
							if($csv_content['parent'][$i]=="yes")
							{
							$all_cats = $parent_product->getCategoryIds();
							}else{
							$all_cats = $product->getCategoryIds();
							}
							
							if(count($all_cats)>1){
								$cat_id = end($all_cats);
							}elseif(count($all_cats)==1){
								$cat_id = $all_cats[0];
							}
							$mapping_id = $csv_content['category'][$i];
							$value = $this->getCategoryMappingValue($cat_id,$mapping_id);
							$formatted_value =$this->getFormatedvalue($value,$csv_content['format'][$i],$csv_content['length'][$i],$store_id,$field_format);
							$row[$csv_content['name'][$i]] = $formatted_value;
							//array_push($row,$formatted_value);
							break;
						case 'text':
							$value = $csv_content['txt'][$i];
							$formatted_value =$this->getFormatedvalue($value,$csv_content['format'][$i],$csv_content['length'][$i],$store_id,$field_format);
							$row[$csv_content['name'][$i]] = $formatted_value;
							//array_push($row,$formatted_value);
							break;
					}
				}
			}
			
			return $row;
		}else {
			return $row;
		}
		
	}
	public function getFormatedvalue($value,$format,$length,$store_id,$field_format){
		
		$value = ($length > 0 ? substr((string)$value, "0", $length)  : (string)$value);
		switch($format){
						case 'strip_tags':
							$formated_value = strip_tags($value);
							break;
						case 'html_escape':
							$formated_value = htmlspecialchars($value);
							break;
						case 'date':
							$date=date_create($value);
							$formated_value = date_format($date,$field_format['dateformat']);
							break;
						case 'price':
						
						$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
						$current_currency = $objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($field_format['currency']);
						$currencies_codes = $this->_systemStore->getStore($store_id)->getAvailableCurrencyCodes();
						$from_currency_code = $this->_systemStore->getStore($store_id)->getBaseCurrencyCode();
						$to_currency_code = $currencies_codes[$field_format['currency']];
					
							$currency = $objectManager->create('Magento\Directory\Helper\Data');
							//echo "value::".$value;
							$converted_price = $currency->currencyConvert($value,$from_currency_code,$to_currency_code);
							if($field_format['currencycode']==1)
							{
								//$converted_price = $to_currency_code." ".number_format($converted_price ,$field_format['numberofdecimalpoint']);
								$converted_price = number_format($converted_price ,$field_format['numberofdecimalpoint']). " ".$to_currency_code;
												
							}else{
								$converted_price = $this->_priceCurrency->format($converted_price,
												$includeContainer = false,
												$field_format['numberofdecimalpoint'],
												$scope = null,
												$to_currency_code);
							}
							
							// Instance of Object Manager
							/*
							$priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data'); // Instance of Pricing Helper
							$pri_value = $priceHelper->currency($value, true, false);
							echo $pri_value_Formatted = $priceHelper->getFormatedPrice($value);
							*/
							//it return price according to current store from base currency
							$formated_value = ($length > 0 ? substr((string)$converted_price,"0",$length)  : (string)$converted_price);
							
				
							break;
						case 'integer':
							$formated_value = ($length > 0 ? substr(intval($value),"0",$length)  : intval($value));
							break;
						case 'lowercase':
							$formated_value = ($length > 0 ? substr(strtolower((string)$value),"0",$length)  : strtolower((string)$value));
							break;
						case 'uppercase ':
							$formated_value = ($length > 0 ? substr(strtoupper((string)$value),"0",$length)  : strtoupper((string)$value));
							break;
						case 'capitalize ':
							$formated_value = ($length > 0 ? substr(ucfirst((string)$value),"0",$length)  : ucfirst((string)$value));
							break;
						default:
							$formated_value = $value;
							return $formated_value;
							break;
						
					}
			return $formated_value;
	}
	public function getCategoryMappingValue($cat_id,$mapping_id){
		
		$this->_mapping = $this->_om->create('Magebees\Productfeed\Model\Mapping')->load($mapping_id);
		$mapping_content = $this->_mapping->getMappingcontent();
		if($this->_mapping->isJSON($mapping_content)){
			$mapping_content_arr = json_decode($mapping_content, true);
		}
		if(isset($mapping_content_arr[$cat_id])){
			$mapping_value = $mapping_content_arr[$cat_id];
		}else{
			$mapping_value = "";
		}
		return $mapping_value;
	}
	public function checkProductValue($product,$attribute_code,$operator,$Check_value){
		
		if($attribute_code=='please-select'){
			return '';
		}
		$pro_attr_value = NULL;
		$attribute = $this->getAttribute($attribute_code);
		$pro_info = $product->getData();
		
		unset($pro_info['image']);
		unset($pro_info['small_image']);
		unset($pro_info['thumbnail']);
		unset($pro_info['swatch_image']);
		unset($pro_info['final_price']);
		unset($pro_info['quantity_and_stock_status']);
		
		if ((!$attribute)&& (empty($pro_info[$attribute_code]))){
			/* Custom Product Information*/
			foreach($this->_templates->getAttributeList() as $key => $list):
				foreach($list as $list_key => $value):
					if($attribute_code==$list_key):
					$parent_attribute = $key;
					break;
					endif;
				endforeach;
			endforeach;
			$pro_attr_value = '';
			switch($parent_attribute){
						case 'Primary Attribute':
							if($attribute_code=="url"){
							$pro_attr_value = $product->getProductUrl();
							}
							break;
						case 'Price Attribute':
							if($product->getTypeId()=='bundle'){
								if($attribute_code=="price_type"){
								$pro_attr_value = $product->getPriceType() == 0 ? "dynamic" : "fixed";
								}
								if($attribute_code=="price_view"){
								$pro_attr_value = $product->getPriceView() == 0 ? "price range" : "as low as";
								}
								}
								if($attribute_code=="final_price"){
								$pro_attr_value = $product->getFinalPrice();
								}
								if($attribute_code=="price"){
								$pro_attr_value = $product->getPrice();
								}
								if($attribute_code=="special_price"){
								$pro_attr_value = $product->getSpecialPrice();
								}
								break;
						case 'Image Attribute':
							$store = $this->_om->create('Magento\Store\Model\StoreManagerInterface')->getStore();
							if($attribute_code=="image"){
								$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
							}else if($attribute_code=="small_image"){
								$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSmallImage();
							}elseif($attribute_code=="thumbnail"){
								$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
							}elseif($attribute_code=="swatch_image"){
								$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSwatchImage();
							}elseif(($attribute_code=="gallery")||(strpos($attribute_code, 'image_') === 0)){
								$gimages = $this->_om->create('\Magento\Catalog\Model\Product')->load($product->getId())->getMediaGalleryImages();
								$store = $this->_om->create('Magento\Store\Model\StoreManagerInterface')->getStore();
								$i=0;
								$image_count = count($gimages);
								$pro_attr_value_list = '';
								if($image_count>0){
								foreach($gimages as $_image){
								if($_image->getMediaType()=="image"){
									if($attribute_code=="gallery"){
										$pro_attr_value = $_image->getUrl()."|";
										if($i==0){
											$i++;
											//$pro_attr_value = $_image->getUrl()."|";
											$pro_attr_value_list = $_image->getUrl();
										}else{
											//$pro_attr_value .= $_image->getUrl();
											$pro_attr_value_list .= $_image->getUrl();
										}
										
									}
									if("image_".$_image->getPosition()==$attribute_code){
										$pro_attr_value = $_image->getUrl();
										break;
									}
									
								}
								 
								
								}
								}
								if($attribute_code=="gallery"){
									$pro_attr_value = $pro_attr_value_list;
								}
								
								
							}
							break;
						case 'Stock Qty Attribute':
							/* Stock Code */
							$stockRegistry = $this->_om->create('Magento\CatalogInventory\Api\StockRegistryInterface');
							$stockItem = $stockRegistry->getStockItem($product->getId(),$product->getStore()->getWebsiteId());
							/* End of Stock Code */
							if($attribute_code=="is_in_stock"){
								if ($stockItem) {
								$pro_attr_value = $stockItem->getIsInStock();
								break;
								}else{
								$pro_attr_value = "";	
								break;
								}
							}
							if($attribute_code=="quantity_and_stock_status"){
								if ($stockItem) {
								$pro_attr_value = $stockItem->getQty();
								break;
								}else{
								$pro_attr_value = "";	
								break;
								}
							}
						case 'Category Attribute':
							$category_model = $this->_om->create('\Magento\Catalog\Model\Category');
							$all_cats = $product->getCategoryIds();
							$pro_attr_value = "";
							if(count($all_cats)>0){
								if($attribute_code=="category.entity_id"){
									$pro_attr_value = end($all_cats);
									
								}
								if($attribute_code=="category"){
									$cat_id = end($all_cats);
									$category = $category_model->setStoreId("1")->load($cat_id);
									$pro_attr_value = $category->getName();
								}
								if($attribute_code=="category.url"){
									$cat_id = end($all_cats);
									$category = $category_model->setStoreId("1")->load($cat_id);
									$pro_attr_value = $category->getUrl();
								}
								if($attribute_code=="category.path"){
								/* Category Code */
								$main_cnt = count($all_cats);
								$cat_str_main = ''; 
								$j = 0;
								foreach($all_cats as $ac){
									$cat_str = "";
									$category = $category_model->load($ac);
									$pathIds = explode("/",$category->getPath());
									foreach($pathIds as $catid){
										$category_sub = $category_model->setStoreId("1")->load($catid);
										if($category_sub->getLevel() == 0){
											continue;
										}
										if($category_sub->getLevel() == 1){
											//$cat_str = $category_sub->getName();
											continue;
										}
										if($category_sub->getLevel() == 2){
											$cat_str = $category_sub->getName();
										}else if($category_sub->getLevel() > 2){
											$cat_str = $cat_str."/".$category_sub->getName();
										}
									}
									if($j < 1){
										$cat_str_main = $cat_str;
									}else {
										$cat_str_main = trim($cat_str_main .",".$cat_str);
									}
									$j = $j+1;						
								}
								$cat_str_main = ltrim ($cat_str_main, ",");
								$pro_attr_value = $cat_str_main;
								
								}
							}
							else{
								$pro_attr_value = "";
							}
							break;
						default:
							$pro_attr_value = "";

					}
					if($operator=="=="){
						if($Check_value==$pro_attr_value){
							return 1;
						}
					}else if($operator=="=="){
						if($pro_attr_value==$Check_value){
							return 1;
						}
					}else if($operator=="!="){
						if($pro_attr_value!=$Check_value){
							return 1;
						}
					}else if($operator==">"){
						if($pro_attr_value > $Check_value){
							return 1;
						}
					}else if($operator==">="){
						if($pro_attr_value >= $Check_value){
							return 1;
						}
					}else if($operator=="<"){
						if($pro_attr_value < $Check_value){
							return 1;
						}
					}else if($operator=="<="){
						if($pro_attr_value <= $Check_value){
							return 1;
						}
					}else if($operator=="{}"){
						if (is_array($pro_attr_value)) {
							if (in_array($Check_value, $pro_attr_value)){
							 return 1;
							} 
						} else if (is_object($pro_attr_value)){
							$pro_attr_value = $pro_attr_value->getText();
							if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false)
							{
								return 1;
							}
						}else{
							if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false)
							{
								return 1;
							}
						}
						
					}else if($operator=="!{}"){
						
						if (is_array($pro_attr_value)) {
							if (!in_array($Check_value, $pro_attr_value)){
							 return 1;
							} 
						} else if (is_object($pro_attr_value)){
							$pro_attr_value = $pro_attr_value->getText();
							if (strpos($pro_attr_value, '"'.$Check_value.'"') === false)
							{	
								return 1;
							}
						}else{
							if (strpos($pro_attr_value, '"'.$Check_value.'"') === false)
							{
								return 1;
							}
						}
					}else if($operator=="()"){
						 if (is_array($pro_attr_value)) {
							if (in_array($Check_value, $pro_attr_value)){
							 return 1;
							} 
						} else if (is_object($pro_attr_value)){
							$pro_attr_value = $pro_attr_value->getText();
							if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false)
							{	
								return 1;
							}
						}else{
							if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false)
							{
								return 1;
							}
						}
						
					}else if($operator=="!()"){
						 if (is_array($pro_attr_value)) {
							
							if (!in_array($Check_value, $pro_attr_value)){
							 return 1;
							} 
						} else if (is_object($pro_attr_value)){
							$pro_attr_value = $pro_attr_value->getText();
							if (strpos($pro_attr_value, '"'.$Check_value.'"') === false)
							{	
								return 1;
							}
						}else{
							if (strpos($pro_attr_value, '"'.$Check_value.'"') === false)
							{
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
				$this->messageManager->addError(__('Invalid option ID specified for %1 (%2), skipping the record.', $attribute_code,$pro_attr_value)));
				return '';
			}
			if (is_array($option)) {
				$pro_attr_value = join(",", $option);
			} else if (is_object($option)){
				$pro_attr_value = $option->getText();
			}else{
				$pro_attr_value = $option;
			}
			unset($option);
			} elseif (is_array($pro_attr_value)) {
				$pro_attr_value = join(",", $option);
			}
			if($operator=="=="){
						if($Check_value==$pro_attr_value){
							return 1;
						}
					}else if($operator=="=="){
						if($pro_attr_value==$Check_value){
							return 1;
						}
					}else if($operator=="!="){
						if($pro_attr_value!=$Check_value){
							return 1;
						}
					}else if($operator==">"){
						if($pro_attr_value > $Check_value){
							return 1;
						}
					}else if($operator==">="){
						if($pro_attr_value >= $Check_value){
							return 1;
						}
					}else if($operator=="<"){
						if($pro_attr_value < $Check_value){
							return 1;
						}
					}else if($operator=="<="){
						if($pro_attr_value <= $Check_value){
							return 1;
						}
					}else if($operator=="{}"){
						if (is_array($pro_attr_value)) {
							if (in_array($Check_value, $pro_attr_value)){
							 return 1;
							} 
						} else if (is_object($pro_attr_value)){
							$pro_attr_value = $pro_attr_value->getText();
							if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false)
							{
								return 1;
							}
						}else{
							if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false)
							{
								return 1;
							}
						}
						
					}else if($operator=="!{}"){
						
						if (is_array($pro_attr_value)) {
							if (!in_array($Check_value, $pro_attr_value)){
							 return 1;
							} 
						} else if (is_object($pro_attr_value)){
							$pro_attr_value = $pro_attr_value->getText();
							if (strpos($pro_attr_value, '"'.$Check_value.'"') === false)
							{	
								return 1;
							}
						}else{
							if (strpos($pro_attr_value, '"'.$Check_value.'"') === false)
							{
								return 1;
							}
						}
					}else if($operator=="()"){
						 if (is_array($pro_attr_value)) {
							if (in_array($Check_value, $pro_attr_value)){
							 return 1;
							} 
						} else if (is_object($pro_attr_value)){
							$pro_attr_value = $pro_attr_value->getText();
							if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false)
							{	
								return 1;
							}
						}else{
							if (strpos($pro_attr_value, '"'.$Check_value.'"') !== false)
							{
								return 1;
							}
						}
						
					}else if($operator=="!()"){
						 if (is_array($pro_attr_value)) {
							if (!in_array($Check_value, $pro_attr_value)){
							 return 1;
							} 
						} else if (is_object($pro_attr_value)){
							$pro_attr_value = $pro_attr_value->getText();
							if (strpos($pro_attr_value, '"'.$Check_value.'"') === false)
							{	
								return 1;
							}
						}else{
							if (strpos($pro_attr_value, '"'.$Check_value.'"') === false)
							{
								return 1;
							}
						}
					}
					return 0;
	}
	public function getProductAttributeValue($product,$attr_code)
	{	
		if($attr_code=="please-select"){
			return '';
		}
		$attribute = $this->getAttribute($attr_code);
		$pro_info = $product->getData();
		
		unset($pro_info['image']);
		unset($pro_info['small_image']);
		unset($pro_info['thumbnail']);
		unset($pro_info['swatch_image']);
		unset($pro_info['quantity_and_stock_status']);
		unset($pro_info['final_price']);
		
		if ((!$attribute)&& (empty($pro_info[$attr_code]))){
			/* Custom Product Information*/
			foreach($this->_templates->getAttributeList() as $key => $list):
				foreach($list as $list_key => $value):
					if($attr_code==$list_key):
					$parent_attribute = $key;
					break;
					endif;
				endforeach;
			endforeach;
			$pro_attr_value = '';
			switch($parent_attribute){
						case 'Primary Attribute':
							if($attr_code=="url"){
							$pro_attr_value = $product->getProductUrl();
							}
							break;
						case 'Price Attribute':
							if($product->getTypeId()=='bundle'){
								if($attr_code=="price_type"){
								$pro_attr_value = $product->getPriceType() == 0 ? "dynamic" : "fixed";
								}
								if($attr_code=="price_view"){
								$pro_attr_value = $product->getPriceView() == 0 ? "price range" : "as low as";
								}
								}
								if($attr_code=="final_price"){
								$pro_attr_value = $product->getFinalPrice();
								}
								if($attr_code=="price"){
								$pro_attr_value = $product->getPrice();
								}
								if($attr_code=="special_price"){
								$pro_attr_value = $product->getSpecialPrice();
								}
								break;
						case 'Image Attribute':
							$store = $this->_om->create('Magento\Store\Model\StoreManagerInterface')->getStore();
							if($attr_code=="image"){
								$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
							}else if($attr_code=="small_image"){
								$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSmallImage();
							}elseif($attr_code=="thumbnail"){
								$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
							}elseif($attr_code=="swatch_image"){
								$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSwatchImage();
							}elseif(($attr_code=="gallery")||(strpos($attr_code, 'image_') === 0)){
								
								 $gimages = $this->_om->create('\Magento\Catalog\Model\Product')->load($product->getId())->getMediaGalleryImages();
						
								$store = $this->_om->create('Magento\Store\Model\StoreManagerInterface')->getStore();
								$i=0;
								$image_count = count($gimages);
								
								
								
								if ($image_count>0) {
								
								foreach ($gimages as $_image) {
                                if ($_image->getMediaType()=="image") {
                                    if ($attr_code=="gallery") {
										$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' .$_image->getFile()."|";
										 if ($i==0) {
                                            $i++;
                                            //$pro_attr_value = $_image->getUrl()."|";
											$pro_attr_value_list = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product'.$_image->getUrl();
										} else {
                                            //$pro_attr_value .= $_image->getUrl();
											$pro_attr_value_list .= $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product'.$_image->getFile();
											
                                          
                                        }
                                    }
                                    if ("image_".$_image->getPosition()==$attr_code) {
                                       
										$pro_attr_value = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product'.$_image->getFile();
										
                                        break;
                                    }
                                }
                            }
							
								
								if($attr_code=="gallery"){
									$pro_attr_value = $pro_attr_value_list;
								}
								
								}
								
								
								
							}
							break;
						case 'Stock Qty Attribute':
							/* Stock Code */
							$stockRegistry = $this->_om->create('Magento\CatalogInventory\Api\StockRegistryInterface');
							$stockItem = $stockRegistry->getStockItem($product->getId(),$product->getStore()->getWebsiteId());
							/* End of Stock Code */
							if($attr_code=="is_in_stock"){
								if ($stockItem) {
								$pro_attr_value = $stockItem->getIsInStock();
								break;
								}else{
								$pro_attr_value = "";	
								break;
								}
							}
							if($attr_code=="quantity_and_stock_status"){
								if ($stockItem) {
								$pro_attr_value = $stockItem->getQty();
								break;
								}else{
								$pro_attr_value = "";	
								break;
								}
							}
							break;
						case 'Category Attribute':
							$category_model = $this->_om->create('\Magento\Catalog\Model\Category');
							$all_cats = $product->getCategoryIds();
							$pro_attr_value = "";
							if(count($all_cats)>0){
								if($attr_code=="category.entity_id"){
									$pro_attr_value = end($all_cats);
									
								}
								if($attr_code=="category"){
									$cat_id = end($all_cats);
									$category = $category_model->setStoreId($this->store_id)->load($cat_id);
									$pro_attr_value = $category->getName();
								}
								if($attr_code=="category.url"){
									$cat_id = end($all_cats);
									$category = $category_model->setStoreId($this->store_id)->load($cat_id);
									$pro_attr_value = $category->getUrl();
								}
								if($attr_code=="category.path"){
								/* Category Code */
								$main_cnt = count($all_cats);
								$cat_str_main = ''; 
								$j = 0;
								foreach($all_cats as $ac){
									$cat_str = "";
									$category = $category_model->load($ac);
									$pathIds = explode("/",$category->getPath());
									foreach($pathIds as $catid){
										$category_sub = $category_model->setStoreId($this->store_id)->load($catid);
										if($category_sub->getLevel() == 0){
											continue;
										}
										if($category_sub->getLevel() == 1){
											//$cat_str = $category_sub->getName();
											continue;
										}
										if($category_sub->getLevel() == 2){
											$cat_str = $category_sub->getName();
										}else if($category_sub->getLevel() > 2){
											$cat_str = $cat_str."/".$category_sub->getName();
										}
									}
									if($j < 1){
										$cat_str_main = $cat_str;
									}else {
										$cat_str_main = trim($cat_str_main .",".$cat_str);
									}
									$j = $j+1;						
								}
								
								$cat_str_main = ltrim ($cat_str_main, ",");
								$pro_attr_value = $cat_str_main;
								
								}
							}
							else{
								$pro_attr_value = "";
							}
							break;
						case 'Dynamic Attributes' : 
							$conditions = $this->_dynamicattributesResource->getConditionByCode($attr_code);
							foreach($conditions as $orkey => $orValues):
							$or_result = $orValues['result']['value'];
							$attributes = $orValues['attribute'];
							$operators = $orValues['operator'];
							$check_values = $orValues['value'];
							$result = array();
							foreach(array_keys($attributes) as $key => $value):
								$result[] = $this->checkProductValue($product,$attributes[$value][0],$operators[$value][0],$check_values[$value][0]);
							endforeach;
							$result = array_filter($result);
							if(count(array_unique($result)) === 1)  
							{
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
		
		if (!$attribute->isEmpty()&&$attribute->usesSource()) {
		if ($attribute->usesSource()) {
			$option = $attribute->getSource()->getOptionText($pro_attr_value);
			if ($pro_attr_value && empty($option) && $option != '0') {
			$this->addException(
				$this->messageManager->addError(__('Invalid option ID specified for %1 (%2), skipping the record.', $attr_code,$pro_attr_value)));
				return '';
			}
			if (is_array($option)) {
				$pro_attr_value = join(",", $option);
			} else if (is_object($option)){
				$pro_attr_value = $option->getText();
			}else{
				$pro_attr_value = $option;
			}
			unset($option);
			}
	}
	elseif (is_array($pro_attr_value)) {
				$pro_attr_value = join(",", $option);
			}
		
		
		return $pro_attr_value;
		
	}
	
	public function getAttribute($code)
    {	
		$this->_product = $this->_om->create('\Magento\Catalog\Model\Product');
		// image,small_image,thumbnail,swatch_image and also check image_ tag not allow for the attribute
		
		$image_tag = preg_match("#^image_(.*)$#i", $code);
		if(($code!="tier_price")&&($code!="image")&&($code!="small_image")&&($code!="thumbnail")&&($code!="swatch_image")&&($image_tag!="1")&&($code!="gallery")&&($code!="quantity_and_stock_status")&&($code!="category.entity_id")&&($code!="category.url")){
			
			if (!isset($this->_attributes[$code])) {
            $this->_attributes[$code] = $this->_product->getResource()->getAttribute($code);
			}
			return $this->_attributes[$code];
		}else{
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
         ob_clean();flush();
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
	public function isJSON($string){
			return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
	}
	
}
