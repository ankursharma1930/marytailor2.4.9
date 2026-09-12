<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Model;

class Templates extends \Magento\Framework\Model\AbstractModel
{
    
    protected $_product;
    protected $context;
    protected $_attr_config;
    protected $_dynamicattributesResource;
    protected $_attributes_collection;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product $product,
        \Magento\Eav\Model\Config $attr_config,
        \Magebees\Productfeed\Model\ResourceModel\Templates $templates,
        \Magebees\Productfeed\Model\ResourceModel\Dynamicattributes $dynamicattributesResource,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributes_collection
    ) {
        $this->_dynamicattributesResource = $dynamicattributesResource;
        $this->_product = $product;
        $this->_attr_config = $attr_config;
        $this->_attributes_collection = $attributes_collection;
        parent::__construct($context, $registry);
    }
    
    
    
    
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Magebees\Productfeed\Model\ResourceModel\Templates');
    }
    public function getTemplateType()
    {
        return [
            ['value' => 'xml', 'label' => __('XML')],
            ['value' => 'csv', 'label' => __('CSV')],
           
        ];
    }
    public function getCsvHeader()
    {
        return [
            ["value" => "1", "label" => __('Yes')],
            ["value" => "0", "label" => __('No')]
            
        ];
    }
    public function getCsvEnclosure()
    {
        return [
            ["value" => "none", "label" => __('None')],
            ["value" => "double_quote", "label" => __('Double Quote (")')],
            ["value" => "single_quote", "label" => __('Single Quote')],
            ["value" => "space", "label" => __('Space')]
            
        ];
    }
    public function getCsvDelimiter()
    {
        return [
            ["value" => "comma", "label" => __('Comma (,)')],
            ["value" => "semicolon", "label" => __('Semicolon (;)')],
            ["value" => "pipe", "label" => __('Pipe (|)')],
            ["value" => "tab", "label" => __('Tab')]
        ];
    }
    public function getColumnType()
    {
        return [
            ["value" => "attribute", "label" => __('Attribute')],
            //["value" => "custom_field", "label" => __('Custom Field')],
            ["value" => "category", "label" => __('Categories')],
            ["value" => "text", "label" => __('Text')]
            //["value" => "images", "label" => __('Images')]
        ];
    }
    public function getMetaTag()
    {
        return [
            ["value" => "meta_title", "label" => __('Title')],
            ["value" => "meta_description", "label" => __('Description')],
            ["value" => "meta_keyword", "label" => __('Keywords')],
            ["value" => "short_description", "label" => __('Short Description')],
            ["value" => "description", "label" => __('Full Description')]
        ];
    }
    public function getImageList()
    {
        return [
            ["value" => "image_1", "label" => __('Image 1')],
            ["value" => "image_2", "label" => __('Image 2')],
            ["value" => "image_3", "label" => __('Image 3')],
            ["value" => "image_4", "label" => __('Image 4')],
            ["value" => "image_5", "label" => __('Image 5')],
            ["value" => "image_6", "label" => __('Image 6')],
            ["value" => "image_7", "label" => __('Image 7')],
            ["value" => "image_8", "label" => __('Image 8')],
            ["value" => "image_9", "label" => __('Image 9')],
            ["value" => "image_10", "label" => __('Image 10')]
        ];
    }
    public function getFormat()
    {
        return [
            ["value" => "as_is", "label" => __('As Is')],
            ["value" => "strip_tags", "label" => __('Strip Tags')],
            ["value" => "html_escape", "label" => __('HTML Escape')],
            ["value" => "lowercase", "label" => __('Lowercase')],
            ["value" => "uppercase", "label" => __('Uppercase')],
            ["value" => "capitalize", "label" => __('Capitalize')],
            ["value" => "date", "label" => __('Date')],
            ["value" => "price", "label" => __('Price')],
            ["value" => "integer", "label" => __('Integer')]
            
            
        ];
    }
    public function getisParent()
    {
        return [
            ["value" => "no", "label" => __('No')],
            ["value" => "yes", "label" => __('Yes')]
        ];
    }
    public function getImageFormat()
    {
        return [
            ["value" => "base", "label" => __('Base')],
            ["value" => "75x75", "label" => __('75 x 75')],
            ["value" => "135x135", "label" => __('135 x 135')],
            ["value" => "265x265", "label" => __('265 x 265')]
        ];
    }
    
    
    public function getAttributeList()
    {
        $dynamic_attribute_list = [];
        $dynamic_attributes = $this->_dynamicattributesResource->getAllCustomAttributes();
        if (count($dynamic_attributes)>0) {
            $dynamic_attribute_list = [];
            foreach ($dynamic_attributes as $attr_key => $att_values) :
                $dynamic_attribute_list[$att_values['code']] = $att_values['name'];
            endforeach;
        }
        $attributes_custom = [];
        $allAttributeCodes = [];
        $attributes_collection = $this->_attributes_collection->getItems();
        
        foreach ($attributes_collection as $attribute) {
            if ($attribute->getFrontendLabel()!="") {
                $attributes_custom[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
            }
        }
        
        $attribute_front_type = ['text','textarea','date','boolean','multiselect','select','price','media_image','weee','swatch_visual','swatch_text'];
        foreach ($attribute_front_type as $type) :
             $customer_attributes = $this->_product->getAttributes();
            
            $attributes = $this->_attr_config->getEntityType('catalog_product')->getAttributeCollection()->addFieldToFilter('frontend_input', $type);
            
            
            if (count($attributes->getData())>0) {
                foreach ($attributes as $attr) {
                    $attr_code = $attr->getData('attribute_code');
                    if ($attr->getData('frontend_label')!="") {
                        $attribute_list[$type][$attr_code] = $attr->getData('frontend_label');
                        $attributes_custom[$attr_code] = $attr->getData('frontend_label');
                    }
                }
            }
        endforeach;
        
        unset($attributes_custom['tier_price']);
        unset($attributes_custom['minimal_price']);
        unset($attributes_custom['image_label']);
        unset($attributes_custom['small_image_label']);
        unset($attributes_custom['thumbnail_label']);
        unset($attributes_custom['category_ids']);
        
        $final_attribute_list = [];
            $custom_primary_attribute = ['description'=>"",'meta_description'=>"",
            'meta_keyword'=>"",'meta_title'=>"",'name'=>"",'entity_id'=>"Product Id",'type_id'=>"Product Type",'url'=>"Product Url",'sku'=>"",'short_description'=>"",'status'=>"",'url_key'=>"",'visibility'=>""];
            $extra_primart_attribute = ['entity_id'=>"Product Id",'type_id'=>"Product Type",'url'=>"Product Url"];
            $cus_primary_attr = array_intersect_key($attributes_custom, $custom_primary_attribute);
            $primary_attribute = array_merge($cus_primary_attr, $extra_primart_attribute);

            asort($primary_attribute);

            $custom_price_attribute = [
            'final_price'=> "Final Price",'price'=> "Price",'special_price'=>"Special Price"];

            $price_attr = array_intersect_key($attributes_custom, array_flip(preg_grep('/price/', array_keys($attributes_custom))));
            /* Remove Tire price because it is base on customer group so no need of it*/
            unset($price_attr['tier_price']);
            unset($price_attr['minimal_price']);
            $price_attribute = array_merge($custom_price_attribute, $price_attr);
            asort($price_attribute);

            $image_attr = array_intersect_key($attributes_custom, array_flip(preg_grep('/image/', array_keys($attributes_custom))));
            $thumbnail_attr = array_intersect_key($attributes_custom, array_flip(preg_grep('/thumbnail/', array_keys($attributes_custom))));
            $gallery_images = ['gallery'=>'Gallery','image_1'=>"Image 1",'image_2'=>"Image 2",'image_3'=>"Image 3",'image_4'=>"Image 4",'image_5'=>"Image 5",'image_6'=>"Image 6",'image_7'=>"Image 7",'image_8'=>"Image 8",'image_9'=>"Image 9",'image_10'=>"Image 10"];
            $img_attribute = array_merge($image_attr, $thumbnail_attr);
            asort($img_attribute);
            $image_attribute = array_merge($img_attribute, $gallery_images);
            $category_attribute = [
            'category.entity_id'=>"Category Id",'category'=> "Category Name",'category.url'=>"Category Url",'category.path'=>"Category Path (Category &gt; Sub Category)"];
            $category_attr = array_intersect_key($attributes_custom, array_flip(preg_grep('/categor/', array_keys($attributes_custom))));
            $category_attribute = array_merge($category_attr, $category_attribute);
            asort($category_attribute);

            $stock_qty_attr = [
            'is_in_stock' =>"Is In Stock"];
            $qty_attr = array_intersect_key($attributes_custom, array_flip(preg_grep('/quantity/', array_keys($attributes_custom))));
            $stock_qty_attribute = array_merge($qty_attr, $stock_qty_attr);

            asort($stock_qty_attribute);
            $other_attribute_list_1 = array_diff_key($attributes_custom, $primary_attribute);
            $other_attribute_list_2 = array_diff_key($other_attribute_list_1, $price_attribute);
            $other_attribute_list_3 = array_diff_key($other_attribute_list_2, $image_attribute);
            $other_attribute_list_4 = array_diff_key($other_attribute_list_3, $category_attribute);
            $other_attribute_list_5 = array_diff_key($other_attribute_list_4, $stock_qty_attribute);
            asort($other_attribute_list_5);
            $final_attribute_list['Primary Attribute'] = $primary_attribute;
            $final_attribute_list['Price Attribute'] = $price_attribute;
            $final_attribute_list['Image Attribute'] = $image_attribute;
            $final_attribute_list['Category Attribute'] = $category_attribute;
            $final_attribute_list['Stock Qty Attribute'] = $stock_qty_attribute;
            $final_attribute_list['Other Attributes'] = $other_attribute_list_5;
            if (count($dynamic_attribute_list)>0) {
                $final_attribute_list['Dynamic Attributes'] = $dynamic_attribute_list;
            }

            
            return $final_attribute_list;
    }
    public function getDays()
    {
        return [
            ["value" => "0", "label" => __('Sunday')],
            ["value" => "1", "label" => __('Monday')],
            ["value" => "2", "label" => __('Tuesday')],
            ["value" => "3", "label" => __('Wednesday')],
            ["value" => "4", "label" => __('Thursday')],
            ["value" => "5", "label" => __('Friday')],
            ["value" => "6", "label" => __('Saturday')]
        ];
    }
    public function getTime()
    {
        return [
            ["value" => "0", "label" => __('00:00 AM')],
            ["value" => "60", "label" => __('01:00 AM')],
            ["value" => "120", "label" => __('02:00 AM')],
            ["value" => "180", "label" => __('03:00 AM')],
            ["value" => "240", "label" => __('04:00 AM')],
            ["value" => "300", "label" => __('05:00 AM')],
            ["value" => "360", "label" => __('06:00 AM')],
            ["value" => "420", "label" => __('07:00 AM')],
            ["value" => "480", "label" => __('08:00 AM')],
            ["value" => "540", "label" => __('09:00 AM')],
            ["value" => "600", "label" => __('10:00 AM')],
            ["value" => "660", "label" => __('11:00 AM')],
            ["value" => "720", "label" => __('12:00 AM')],
            ["value" => "780", "label" => __('01:00 PM')],
            ["value" => "840", "label" => __('02:00 PM')],
            ["value" => "900", "label" => __('03:00 PM')],
            ["value" => "960", "label" => __('04:00 PM')],
            ["value" => "1020", "label" => __('05:00 PM')],
            ["value" => "1080", "label" => __('06:00 PM')],
            ["value" => "1140", "label" => __('07:00 PM')],
            ["value" => "1200", "label" => __('08:00 PM')],
            ["value" => "1260", "label" => __('09:00 PM')],
            ["value" => "1320", "label" => __('10:00 PM')],
            ["value" => "1380", "label" => __('11:00 PM')]
        ];
    }
}
