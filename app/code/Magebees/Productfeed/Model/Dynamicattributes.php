<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Model;

class Dynamicattributes extends \Magento\Framework\Model\AbstractModel
{
    protected $_catalogProduct;
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magebees\Productfeed\Model\ResourceModel\Dynamicattributes $dynamicattributes,
        \Magento\Catalog\Model\ResourceModel\Product $catalogProduct,
        \Magento\Framework\Registry $registry
    ) {
        $this->_catalogProduct = $catalogProduct;
        parent::__construct($context, $registry);
    }
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
       // parent::_construct();
        $this->_init('Magebees\Productfeed\Model\ResourceModel\Dynamicattributes');
    }
    
    public function getConditionDetails($code, $selected_value, $selected_oprtator, $or_counting, $and_counting)
    {
        $attribute_result = [];
        $id = "condition-".$or_counting."-result-value-".$and_counting;
		$tr_id ="condition-".$and_counting."-value";
		
        if($selected_value!=""){
		$result = "<td id=".$tr_id."><input type='text' name='condition[".$or_counting."][value][".$and_counting."][]' id=".$id." class='input-text admin__control-text' 41 value='$selected_value'></td>";
		}else{
		$result = "<td id=".$tr_id."><input type='text' name='condition[".$or_counting."][value][".$and_counting."][]' id=".$id." class='input-text admin__control-text' ></td>";
		}
		
        
        $operator_list = "";
        $operators[0]['label'] = 'is';
        $operators[0]['value'] = '==';
        $operators[1]['label'] = 'is not';
        $operators[1]['value'] = '!=';
        $operators[2]['label'] = 'equals or greater than';
        $operators[2]['value'] = '>=';
        $operators[3]['label'] = 'greater than';
        $operators[3]['value'] = '>';
        $operators[4]['label'] = 'less than';
        $operators[4]['value'] = '<';
        $operators[5]['label'] = 'equals or less than';
        $operators[5]['value'] = '<=';
        $operators[6]['label'] = 'contains';
        $operators[6]['value'] = '{}';
        $operators[7]['label'] = 'does not contain';
        $operators[7]['value'] = '!{}';
		$operators[8]['label'] = 'is one of';
        $operators[8]['value'] = '()';
        $operators[9]['label'] = 'is not one of';
        $operators[9]['value'] = '!()';
        
		
         //   $id = "condition-".$and_counting."-value";
        $operator_list = "<td><select or=".$or_counting." and=".$and_counting." name=condition[".$or_counting."][operator][".$and_counting."][] id=insert_condition_operator_".$and_counting." class='select admin__control-select'>";
        foreach ($operators as $operator) {
            if (is_array($operator['value'])) {
                foreach ($operator as $opt) {
                    if (is_array($opt)) {
                        foreach ($opt as $opt) {
                            if (isset($opt['value'])) {
                                if ($opt['value']==$selected_oprtator) {
                                    $operator_list .= '<option value="'.$opt['value'].'" selected>'.$opt['value'].'</option>';
                                } else {
                                    $operator_list .= '<option value="'.$opt['value'].'">'.$opt['value'].'</option>';
                                }
                            }
                        }
                    }
                }
            } else {
                if ($operator['value']==$selected_oprtator) {
                    $operator_list .= '<option value="'.$operator['value'].'" selected>'.$operator['label'].'</option>';
                } else {
                    $operator_list .= '<option value="'.$operator['value'].'">'.$operator['label'].'</option>';
                }
            }
        }
             $operator_list .= "</select></td>";
        if (!$code) {
            $attribute_result['attribute_options'] = $result;
            $attribute_result['operators'] = $operator_list;
            return $attribute_result;
        }
        
        $attribute = $this->_catalogProduct->getAttribute($code);
    
        if (!$attribute) {
            $attribute_result['attribute_options'] = $result;
            $attribute_result['operators'] = $operator_list;
            return $attribute_result;
        }

        $dropdowns = ['select', 'multiselect', 'boolean'];
        if (!in_array($attribute->getFrontendInput(), $dropdowns)) {
            $attribute_result['attribute_options'] = $result;
            $attribute_result['operators'] = $operator_list;
            return $attribute_result;
        }
        
        
        
        if ($attribute->getFrontendInput()=='boolean') {
            $options[0]['label'] = 'Yes';
            $options[0]['value'] = '1';
            $options[1]['label'] = 'No';
            $options[1]['value'] = '0';
        } else {
            $options = $attribute->getFrontend()->getSelectOptions();
        }
        $select_operator_list = array();
      /*  $select_operator_list[0]['label'] = 'contains';
        $select_operator_list[0]['value'] = '{}';
        $select_operator_list[1]['label'] = 'does not contain';
        $select_operator_list[1]['value'] = '!{}';
        $select_operator_list[2]['label'] = 'is one of';
        $select_operator_list[2]['value'] = '()';
        $select_operator_list[3]['label'] = 'is not one of';
        $select_operator_list[3]['value'] = '!()';
		*/
		 
		$select_operator_list[0]['label'] = 'is';
        $select_operator_list[0]['value'] = '==';
        $select_operator_list[1]['label'] = 'is not';
        $select_operator_list[1]['value'] = '!=';
        $select_operator_list[2]['label'] = 'equals or greater than';
        $select_operator_list[2]['value'] = '>=';
        $select_operator_list[3]['label'] = 'greater than';
        $select_operator_list[3]['value'] = '>';
        $select_operator_list[4]['label'] = 'less than';
        $select_operator_list[4]['value'] = '<';
        $select_operator_list[5]['label'] = 'equals or less than';
        $select_operator_list[5]['value'] = '<=';
        $select_operator_list[6]['label'] = 'contains';
        $select_operator_list[6]['value'] = '{}';
        $select_operator_list[7]['label'] = 'does not contain';
        $select_operator_list[7]['value'] = '!{}';
		$select_operator_list[8]['label'] = 'is one of';
        $select_operator_list[8]['value'] = '()';
        $select_operator_list[9]['label'] = 'is not one of';
        $select_operator_list[9]['value'] = '!()';
		
        $select_opr_list = "";
                
        $select_opr_list = "<td><select or=".$or_counting." and=".$and_counting." name=condition[".$or_counting."][operator][".$and_counting."][] id=insert_condition_operator_".$or_counting." class='select admin__control-select'>";
        foreach ($select_operator_list as $operator) {
            if (is_array($operator['value'])) {
                foreach ($operator as $opt) {
                    if (is_array($opt)) {
                        foreach ($opt as $opt) {
                            if ($opt['value']==$selected_oprtator) {
                                $select_opr_list .= '<option value="'.$opt['value'].'" selected>'.$opt['value'].'</option>';
                            } else {
                                $select_opr_list .= '<option value="'.$opt['value'].'">'.$opt['value'].'</option>';
                            }
                        }
                    }
                }
            } else {
                if ($operator['value']==$selected_oprtator) {
                    $select_opr_list .= '<option value="'.$operator['value'].'" selected>'.$operator['label'].'</option>';
                } else {
                    $select_opr_list .= '<option value="'.$operator['value'].'">'.$operator['label'].'</option>';
                }
            }
        }
        $select_opr_list .= "</select></td>";
        $id = "condition-".$and_counting."-value";
        
        $result = "<td id=".$id."><select id='condition-".$or_counting."-result-value-".$and_counting."' name='condition[".$or_counting."][value][".$and_counting."][]' class='select admin__control-select' >";
        
        foreach ($options as $option) {
            if (is_array($option['value'])) {
                foreach ($option as $opt) {
                    if (is_array($opt)) {
                        foreach ($opt as $opt) {
                            if (isset($opt['value'])) {
                                 if ($opt['value']==$selected_value) {
                                   $result .= '<option value="'.$opt['value'].'">'.$opt['label'].'</option>';
                                } else {
                                     $result .= '<option value="'.$opt['value'].'">'.$opt['label'].'</option>';
                                }
							}
                        }
                    }
                }
            } else {
				 if ($option['value']==$selected_value) {
                                   $result .= '<option value="'.$option['value'].'" selected>'.$option['label'].'</option>';
                                } else {
                                     $result .= '<option value="'.$option['value'].'">'.$option['label'].'</option>';
                                }
				
              
            }
        }
		
		
        $result .= '</select></td>';
        //$select_opr_list = "";
        $attribute_result['attribute_options'] = $result;
        $attribute_result['operators'] = $select_opr_list;
        return $attribute_result;
    }
}
