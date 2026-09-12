<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Model;

use DOMDocument;
use XmlWriter;
use RecursiveIteratorIterator;
use Zend\Stdlib\ErrorHandler;
use Zend\Uri;
use Zend\View;
use Zend\View\Exception;

class XmlToArray
{
    private static $xml = null;
    private static $encoding = 'UTF-8';
    
    private static $_Xmlexport;
    private static $_Storemanager;
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
        \Magebees\Productfeed\Model\Xmlexport $Xmlexport,
        \Magento\Store\Model\StoreManagerInterface $Storemanager,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $ConfigurableProduct,

                \Magento\Catalog\Model\ProductFactory $Product
    ) {
        self::$_Xmlexport = $Xmlexport;
        self::$_Storemanager = $Storemanager;
        self::$_ConfigurableProduct = $ConfigurableProduct;
        self::$_Product = $Product;
    }
    /**
     * Initialize the root XML node [optional]
     * @param $version
     * @param $encoding
     * @param $format_output
     */
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
    public static function &createArray($input_xml, $itemtag, $product, $feed_data)
    {
        
        
        $xml = self::getXMLRoot();
        if (is_string($input_xml)) {
            $parsed = $xml->loadXML($input_xml);
            if (!$parsed) {
                throw new Exception('[XML2Array] Error parsing the XML string.');
            }
        } else {
            if (get_class($input_xml) != 'DOMDocument') {
                throw new Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
            }
            $xml = self::$xml = $input_xml;
        }
        $array[$xml->documentElement->tagName] = self::convert($xml->documentElement, $itemtag, $product, $feed_data);
        
        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $array;
    }

    /**
     * Convert an Array to XML
     * @param mixed $node - XML as a string or as an object of DOMDocument
     * @return mixed
     */
    private static function &convert($node, $itemtag, $product, $feed_data)
    {
        $output = [];

        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
                $output['@cdata'] = self::getProductValue($product, trim($node->textContent), $feed_data);
                break;

            case XML_TEXT_NODE:
                //print_r($node->textContent);
                $output = self::getProductValue($product, trim($node->textContent), $feed_data);
                //trim($node->textContent);
                break;

            case XML_ELEMENT_NODE:
                // for each child node, call the covert function recursively
                for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
                    $child = $node->childNodes->item($i);
                    //$v = self::convert($child);
                    $v = self::convert($child, $itemtag, $product, $feed_data);
                    
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
                            if (is_array($v) && isset($v['@cdata'])) {
                            //$v = $v['@cdata'];
                                //$cdata_node_text = $v['@cdata'];
                                $productvalue = self::getProductValue($product, trim($node->textContent), $feed_data);
                                $v['@cdata']=$productvalue;
                            } else {
                                $v = self::getProductValue($product, trim($node->textContent), $feed_data);
                            }
                            
                            if ($v=="") {
                                continue;
                            }
                            $output = $v;
                            
                            /*
							if($productvalue==""){
								continue;
							}
							$output = self::getProductValue($product,trim($node->textContent)); 
							*/
                        }
                    }
                }

                if (is_array($output)) {
                    // if only one node of its kind, assign it directly instead if array($value);
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v)==1) {
                            $output[$t] = $v[0];
                            //$output[$t] = self::getProductValue($product,$v[0]);
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
                        //$a[$attrName] = (string) $attrNode->value;
                        $a[$attrName] = self::getProductValue($product, (string) $attrNode->value, $feed_data);
                        //echo $attrNode->value;die;
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
    public static function getProductValue($product, $nodetext, $feed_data)
    {
        
        
        $store_id = $feed_data['storeview'];
        $format_serialized = json_decode($feed_data['format_serialized'], true);
        
        if (strpos($nodetext, "'") !== false) {
            $nodetext = ltrim($nodetext, "'");
            $nodetext = rtrim($nodetext, "'");
            $nodetext = str_replace("'", '"', $nodetext);
        }
        
        $re = '/(["\'])(?:(?=(\\\\?))\2.)*?\1/';
        
        //$re = '/(["\'])(?:(?='(\\\\?))\2.)*?\1/';
        
        $type = null;
        $attr_value = null;
        $format = null;
        $parent = null;
        $cdata = null;
        preg_match_all($re, $nodetext, $matches);
        
        if (count($matches[0])==5) {
            $element_key = ['type','value','format','length','parent'];
        } elseif (count($matches[0])==6) {
            $element_key = ['type','value','format','length','parent','cdata'];
        }
        
        if (!empty($matches[0]) && ((count($matches[0])==5) || (count($matches[0])==6))) {
            $xml_element_matches = array_combine($element_key, $matches[0]);
            if (preg_match('/"/', $xml_element_matches['type'])==1) {
                $type = trim($xml_element_matches['type'], '"');
            } elseif (preg_match("/'/", $xml_element_matches['type'])==1) {
                $type = trim($xml_element_matches['type'], "'");
            }
            if (preg_match('/"/', $xml_element_matches['value'])==1) {
                $attr_value = trim($xml_element_matches['value'], '"');
            } elseif (preg_match("/'/", $xml_element_matches['value'])==1) {
                $attr_value = trim($xml_element_matches['value'], "'");
            }
            if (preg_match('/"/', $xml_element_matches['format'])==1) {
                $format = trim($xml_element_matches['format'], '"');
            } elseif (preg_match("/'/", $xml_element_matches['format'])==1) {
                $format = trim($xml_element_matches['format'], "'");
            }
            if (preg_match('/"/', $xml_element_matches['length'])==1) {
                $length = trim($xml_element_matches['length'], '"');
            } elseif (preg_match("/'/", $xml_element_matches['length'])==1) {
                $length = trim($xml_element_matches['length'], "'");
            }
            if (preg_match('/"/', $xml_element_matches['parent'])==1) {
                $parent = trim($xml_element_matches['parent'], '"');
            } elseif (preg_match("/'/", $xml_element_matches['parent'])==1) {
                $parent = trim($xml_element_matches['parent'], "'");
            }
            if (isset($xml_element_matches['cdata'])) {
                if (preg_match('/"/', $xml_element_matches['cdata'])==1) {
                    $cdata = trim($xml_element_matches['cdata'], '"');
                } elseif (preg_match("/'/", $xml_element_matches['cdata'])==1) {
                    $cdata = trim($xml_element_matches['cdata'], "'");
                }
            }
                            
            $parentId="";
            if ($parent=="yes") {
                if ($product->getTypeId()=="simple") {
                }
                {
                            
                $product_id = $product->getId();
                $parentIds = self::$_ConfigurableProduct->getParentIdsByChild($product_id);
                if (isset($parentIds)) {
                    $parentId = array_shift($parentIds);
                } else {
                    $parentId = "";
                }
                                
                }
                if ($parentId!="") {
                        $parent_product = self::$_Product->create()->load($parentId);
						//$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
						//$parent_product = $objectManager->create('Magento\Catalog\Model\ProductFactory')->create()->load($parentId);

                } else {
                        $parent_product = $product;
                        //self::$_Product->load($product_id);
                }
                $value = self::$_Xmlexport->xmlFieldValue($type, $attr_value, $format, $length, $parent_product, $feed_data);
            } else {
                $value = self::$_Xmlexport->xmlFieldValue($type, $attr_value, $format, $length, $product, $feed_data);
            }
            $output = $value;
        } else {
            $codes = ['{{StoreManager.baseurl}}',
            '{{StoreManager.basesecureurl}}',
            '{{StoreManager.frontname}}',
            '{{StoreManager.formattedaddress}}',
            '{{StoreManager.basecurrencycode}}',
            '{{StoreManager.defaultcurrencycode}}',
            '{{StoreManager.currentcurrencycode}}',
            '{{date}}',
            '{{time}}'
            ];
			 $formated_date = null;
            $formated_time = null;
            if(isset($format_serialized['dateformat']))
            {
			$formated_date = date($format_serialized['dateformat']);
            }
            if(isset($format_serialized['timeformat']))
            {
				$time=time();
				$format_serialized['timeformat'];
				$formated_time = date($format_serialized['timeformat'],$time);
			
            }
            $accurate = [self::$_Storemanager->getStore($store_id)->getBaseUrl(),
            self::$_Storemanager->getStore($store_id)->getBaseSecureUrl(),
            self::$_Storemanager->getStore($store_id)->getFrontendName(),
            self::$_Storemanager->getStore($store_id)->getFormattedAddress(),
            self::$_Storemanager->getStore($store_id)->getBaseCurrencyCode(),
            self::$_Storemanager->getStore($store_id)->getDefaultCurrencyCode(),
            self::$_Storemanager->getStore($store_id)->getCurrentCurrencyCode(),
            $formated_date,
           $formated_time
            ];
                             
            $repalced_nodetext =  str_replace($codes, $accurate, $nodetext);
                                
                                
            $output = $repalced_nodetext;
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
}
