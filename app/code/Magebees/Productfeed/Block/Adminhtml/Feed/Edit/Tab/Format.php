<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

// @codingStandardsIgnoreFile

namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab;


use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Format extends Generic implements TabInterface
{
	protected $_systemStore;
	protected $_yesno;
    /**
     * {@inheritdoc}
     */
	 public function __construct(
		\Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
		\Magento\Config\Model\Config\Source\Yesno $yesno,
        \Magebees\Productfeed\Model\Feed $feed,
        array $data = array()
    ) {
        $this->_systemStore = $context->getStoreManager();
		$this->_yesno = $yesno;
		$this->feed = $feed;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    public function getTabLabel()
    {
        return __('Format Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Format Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
       // print_r(get_class_methods($this->_stockFilter));die;
		$model = $this->_coreRegistry->registry('current_magebees_productfeed_feed');
		$isElementDisabled = false;
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('price_fieldset', ['legend' => __('Price')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
		//print_R(get_class_methods($this->_productType->toOptionArray));die;
         $informations = array();
		 $id = $this->getRequest()->getParam('id');
		 if($id)
		{
        $format_serialized = $model->getFormatSerialized();
		if($this->isJSON($format_serialized)){
				$sub_information = json_decode($format_serialized, true);
				$informations['id'] = $id;
				foreach($sub_information as $subkey => $subvalue):
						$informations[$subkey] = $subvalue;	
					endforeach;
		}
	
		}else{
		$currency = array();
		$currencycode = array();
		$numberofdecimalpoint = array();
		$sepratedecimal = array();
		$dateformat = array();
		$timeformat = array();
		$informations = array();
		$informations = $model->getData();
		$informations['dateformat']= "Y/m/d";
		$informations['timeformat']= "H:i:s";
		$informations['numberofdecimalpoint']= "2";
		
		}
		
		
		$currency = array();
		
		$fieldset->addField(
            'currency',
            'select',
			['name' => 'format[currency]', 
			'label' => __('Price'), 
			'title' => __('Price'), 
			'required' => true, 
			'values'=> $this->_systemStore->getStore()->getAvailableCurrencyCodes()]
        );
		$fieldset->addField(
            'currencycode',
            'select',
			['name' => 'format[currencycode]', 
			'label' => __('Show Currency Abbr'), 
			'title' => __('Show Currency Abbr'), 
			'required' => true,
			'values'    => $this->_yesno->toOptionArray()
			]
		);
		
		$fieldset->addField(
            'numberofdecimalpoint',
            'select',
			['name' => 'format[numberofdecimalpoint]', 
			'label' => __('Number of decimal points'), 
			'title' => __('Number of decimal points'), 
			'required' => true,
			'values'    => [
					'1' => __('One'), 
					'2' => __('Two'), 
					'3' => __('Three'), 
					'4' => __('Four'), 
					]
			]
		);
		
		$fieldset = $form->addFieldset('date_fieldset', ['legend' => __('Date & Time')]);
		
		$fieldset->addField(
            'dateformat',
            'text',
			['name' => 'format[dateformat]', 
			'label' => __('Date'),
			'title' => __('Date')
			]
        );
		$fieldset->addField(
            'timeformat',
            'text',
			['name' => 'format[timeformat]', 
			'label' => __('Time'),
			'title' => __('Date')
			]
        );
		
		
		if($id)
		{
        $conditions_serialized = $model->getConditionsSerialized();
		if($this->isJSON($conditions_serialized)){
				$sub_information = json_decode($conditions_serialized, true);
				$informations['id'] = $id;
				foreach($sub_information as $subkey => $subvalue):
						$informations[$subkey] = $subvalue;	
					endforeach;
			}
			$form->setValues($informations);
		
		}
		else
		{
			//$form->setValues($model->getData());
			$form->setValues($informations);
		}
		
        $this->setForm($form);
        return parent::_prepareForm();
    }
	public function isJSON($string){
		return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
	}
}


