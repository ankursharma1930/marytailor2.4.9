<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Model;

use DOMDocument;
use RecursiveIteratorIterator;
use Zend\Stdlib\ErrorHandler;
use Zend\Uri;
use Zend\View;
use Zend\View\Exception;

class Xmlparser extends \Magento\Framework\Model\AbstractModel
{
    private static $xml = null;
    private static $encoding = 'UTF-8';
    private static $_Xmlexport;
    private static $_ConfigurableProduct;
    private static $_Product;
    /**
     * Initialize the root XML node [optional]
     * @param $version
     * @param $encoding
     * @param $format_output
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magebees\Productfeed\Model\Xmlexport $Xmlexport,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $ConfigurableProduct,
        \Magento\Catalog\Model\Product $Product
    ) {
        self::$_Xmlexport = $Xmlexport;
        self::$_ConfigurableProduct = $ConfigurableProduct;
        self::$_Product = $Product;
        parent::__construct($context, $registry);
    }
     
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true)
    {
        
        self::$xml = new DOMDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
        self::$encoding = $encoding;
    }
 
    /**
     * Convert an XML to Array
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DOMDocument
     */
    public static function &createArray($input_xml, $itemtag, $product)
    {
        $xml = self::getXMLRoot();
        if (is_string($input_xml)) {
            $parsed = $xml->loadXML($input_xml);
            if (!$parsed) {
                throw new Exception\RuntimeException(
                    '[XML2Array] Error parsing the XML string.'
                );
            }
        } else {
            if (get_class($input_xml) != 'DOMDocument') {
                throw new Exception\RuntimeException(
                    '[XML2Array] The input XML object should be of type: DOMDocument.'
                );
            }
            $xml = self::$xml = $input_xml;
        }
        $array[$xml->documentElement->tagName] = self::convert($xml->documentElement, $itemtag, $product);
        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $array;
    }
 
    /**
     * Convert an Array to XML
     * @param mixed $node - XML as a string or as an object of DOMDocument
     * @return mixed
     */
    private static function &convert($node, $itemtag, $product)
    {
        $output = [];
        
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
                //$output['@cdata'] = trim($node->textContent);
                $output['@cdata'] = self::getProductValue($product, trim($node->textContent));
                
                break;
 
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
 
            case XML_ELEMENT_NODE:
                // for each child node, call the covert function recursively
                for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = self::convert($child, $itemtag, $product);
                    
                    if (isset($child->tagName)) {
                        $t = $child->tagName;
                        
                        // assume more nodes of same kind are coming
                        if (!isset($output[$t])) {
                            $output[$t] = [];
                        }
                        //preg_match_all($re, $v, $matches);
                        //$output[$t][] = $matches[0];
                        $output[$t][] = $v;
                    } else {
                        //check if it is not an empty text node
                        if ($v !== '') {
                            /* Change in $v */
                            //$output = $v;
                            
                            if (is_array($v) && isset($v['@cdata'])) {
                                $v = $v['@cdata'];
                            }
                            $output = self::getProductValue($product, $v);
                        }
                    }
                }
 
                if (is_array($output)) {
                    // if only one node of its kind, assign it directly instead if array($value);
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v)==1) {
                            $output[$t] = $v[0];
                            //preg_match_all($re, $v[0], $matches);
                            //$output[$t] = $matches[0];
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
                        //$output = array('@value' => 'ajay4');
                    }
                    $output['@attributes'] = $a;
                }
                break;
        }
        return $output;
    }
    public function find_xml_item($array, $search)
    {
        if (is_array($array)) {
            if (isset($array[$search])) {
                return $array[$search];
            } else {
                foreach ($array as $key => $value) {
                    return $sub = $this->find_xml_item($value, $search);
                }
            }
        }
    }
    public static function getProductValue($product, $nodetext)
    {
        $element_key = ['type','value','format','length','parent','cdata'];
        $re = '/(["\'])(?:(?=(\\\\?))\2.)*?\1/';
        $cdata = null;
        
        preg_match_all($re, $nodetext, $matches);
        if (!empty($matches[0]) && (count($matches[0])==6)) {
            $xml_element_matches = array_combine($element_key, $matches[0]);
                            
            $type = trim($xml_element_matches['type'], '"');
            $attr_value = trim($xml_element_matches['value'], '"');
            $format = trim($xml_element_matches['format'], '"');
            $length = trim($xml_element_matches['length'], '"');
            $parent = trim($xml_element_matches['parent'], '"');
            $cdata = trim($xml_element_matches['cdata'], '"');
            $parentId="";
            if ($parent=="yes") {
                if ($product->getTypeId()=="simple") {
                }
                {
                            
                $product_id = $product->getId();
                $parentIds = $this->_ConfigurableProduct->getParentIdsByChild($product_id);
                $parentId = array_shift($parentIds);
                                
                if (isset($parentIds)) {
                    $parentId = array_shift($parentIds);
                } else {
                    $parentId = "";
                }
                }
                if ($parentId!="") {
                        $parent_product = self::$_Product->load($parentId);
                } else {
                        $parent_product = $product;
                        //self::$_Product->load($product_id);
                }
                            
                $value = self::$_Xmlexport->xmlFieldValue($type, $attr_value, $format, $length, $parent_product);
            } else {
                $value = self::$_Xmlexport->xmlFieldValue($type, $attr_value, $format, $length, $product);
            }
            $output = $value;
        } else {
            /* Static value between tag.*/
            $output = $nodetext;
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
    public function isJSON($string)
    {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
