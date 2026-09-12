<?php
namespace Magebees\Productfeed\Controller\Adminhtml\Dynamicattributes;

use Magento\Framework\Controller\ResultFactory;

class Attroptions extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    protected $_catalogProduct;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product $catalogProduct
    ) {
        parent::__construct($context);
        $this->_catalogProduct = $catalogProduct;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
       
        $attribute_result = [];
         $code = $this->getRequest()->getParam('attribute_code');
         $and_counting = $this->getRequest()->getParam('and_counting');
         $or_counting = $this->getRequest()->getParam('or_counting');
        
        $result = "<input type='text' name='condition[".$or_counting."][value][".$and_counting."][]' id='condition-".$or_counting."-result-value' class='input-text admin__control-text Attroptions.php 39'>";
        
           
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
        
		
        foreach ($operators as $operator) {
            if (is_array($operator['value'])) {
                foreach ($operator as $opt) {
                    if (is_array($opt)) {
                        foreach ($opt as $opt) {
                            if (isset($opt['value'])) {
                                $operator_list .= '<option value="'.$opt['value'].'">'.$opt['value'].'</option>';
                            }
                        }
                    }
                }
            } else {
                $operator_list .= '<option value="'.$operator['value'].'">'.$operator['label'].'</option>';
            }
        }
             
        if (!$code) {
            $attribute_result['attribute_options'] = $result;
            $attribute_result['operators'] = $operator_list;
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($attribute_result);
            return $resultJson;
        
            //$this->getResponse()->setBody($result);
            //return;
        }
        
        $attribute = $this->_catalogProduct->getAttribute($code);
    
        if (!$attribute) {
            //$this->getResponse()->setBody($result);
            $attribute_result['attribute_options'] = $result;
            $attribute_result['operators'] = $operator_list;
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($attribute_result);
            return $resultJson;
            //return;
        }

        $dropdowns = ['select', 'multiselect', 'boolean'];
        if (!in_array($attribute->getFrontendInput(), $dropdowns)) {
            //$this->getResponse()->setBody($result);
            //return;
            $attribute_result['attribute_options'] = $result;
            $attribute_result['operators'] = $operator_list;
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($attribute_result);
            return $resultJson;
        }
        
        /*
		if($attribute->getFrontendInput()=='textarea'){
			$result = '<textarea id="attr_value" name="attr_value"></textarea>';
			$this->getResponse()->setBody($result);
			return;
		}
		*/
        
        if ($attribute->getFrontendInput()=='boolean') {
            $options[0]['label'] = 'Yes';
            $options[0]['value'] = '1';
            $options[1]['label'] = 'No';
            $options[1]['value'] = '0';
        } else {
            $options = $attribute->getFrontend()->getSelectOptions();
        }
        $select_operator_list = array();
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
        foreach ($select_operator_list as $operator) {
            if (is_array($operator['value'])) {
                foreach ($operator as $opt) {
                    if (is_array($opt)) {
                        foreach ($opt as $opt) {
                            if (isset($opt['value'])) {
                                $select_opr_list .= '<option value="'.$opt['value'].'">'.$opt['value'].'</option>';
                            }
                        }
                    }
                }
            } else {
                $select_opr_list .= '<option value="'.$operator['value'].'">'.$operator['label'].'</option>';
            }
        }
        
        /*
		<option value="{}">contains</option><option value="!{}">does not contain</option><option value="()">is one of</option><option value="!()">is not one of</option>
		*/
        
        
        $result = "<select id='condition-".$or_counting."-result-value-".$and_counting."' name='condition[".$or_counting."][value][".$and_counting."][]' class='select admin__control-select'>";
        
        foreach ($options as $option) {
            if (is_array($option['value'])) {
                foreach ($option as $opt) {
                    if (is_array($opt)) {
                        foreach ($opt as $opt) {
                            if (isset($opt['value'])) {
                                $result .= '<option value="'.$opt['value'].'">'.$opt['value'].'</option>';
                            }
                        }
                    }
                }
            } else {
                $result .= '<option value="'.$option['value'].'">'.$option['label'].'</option>';
            }
        }
        $result .= '</select>';
        $attribute_result['attribute_options'] = $result;
        $attribute_result['operators'] = $select_opr_list;
        //$this->getResponse()->setBody($attribute_result);
        
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($attribute_result);
        return $resultJson;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::dynamicattributes');
    }
}
