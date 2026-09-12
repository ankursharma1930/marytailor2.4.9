<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

// @codingStandardsIgnoreFile

namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab;


use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Conditions extends Generic implements TabInterface
{

    /**
     * {@inheritdoc}
     */
	
	protected $_systemStore;
	protected $productVisibility;
	protected $productStatus;
	protected $productType;
	protected $productAttributeset;
	protected $stockFilter;
	 public function __construct(
		\Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
		\Magento\Catalog\Model\Product\Type $productType,
		\Magento\Catalog\Model\Product\Visibility $productVisibility,
		\Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
		\Magento\Catalog\Model\Product\AttributeSet\Options $productAttributeset,
		\Magento\CatalogInventory\Helper\Stock $stockFilter,
		\Magebees\Productfeed\Model\Feed $feed,
        array $data = array()
    ) {
        $this->_systemStore = $systemStore;
		$this->_productStatus = $productStatus;
		$this->_productVisibility = $productVisibility;
		$this->_productType = $productType;
		$this->_productAttributeset = $productAttributeset;
		$this->_stockFilter = $stockFilter;
		
		
		$this->feed = $feed;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    public function getTabLabel()
    {
        return __('Feed Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Feed Information');
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
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Condition Information')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
		//print_R(get_class_methods($this->_productType->toOptionArray));die;
         $informations = array();
		 $id = $this->getRequest()->getParam('id');
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
		$product_type = $informations['producttype'];
		$visibility = $informations['visibility'];
		$status = $informations['status'];
		$stock = $informations['stock'];
		}else{
		$product_type = array();
		$visibility = array();
		$status = array();
		$stock = array();
		}
		
        $list_attribute_set = $this->_productAttributeset->toOptionArray();
		$list_all[] = array('value'=>'*','label'=>'ALL');
		$sorted_attribute_list = array_merge($list_all,$list_attribute_set);
		$fieldset->addField(
            'attributeset',
            'select',
			['name' => 'conditions[attributeset]', 
			'label' => __('Attribute Set'), 
			'title' => __('Attribute Set'), 
			'required' => true, 
			'values'=> $sorted_attribute_list]
        );
		$fieldset->addField(
            'producttype',
            'multiselect',
			['name' => 'conditions[producttype][]', 
			'label' => __('Product Type'), 
			'title' => __('Product Type'), 
			'required' => true,
			'values'=> $this->_productType->toOptionArray()	,
			'value'=> $product_type,			
			]
        );
		
		$fieldset->addField(
            'visibility',
            'multiselect',
			['name' => 'conditions[visibility]', 
			'label' => __('Product Visibility'), 
			'title' => __('Product Visibility'), 
			'required' => true, 
			'values'=> $this->_productVisibility->toOptionArray(),'value'=> $visibility,
			]
        );
		$fieldset->addField(
            'status',
            'select',
			['name' => 'conditions[status]', 
			'label' => __('Product Status'), 
			'title' => __('Product Status'), 
			'required' => true, 
			'values'=> $this->_productStatus->toOptionArray(),'value'=> $status,]
        );
		
		$fieldset->addField(
            'stock',
            'select',
			['name' => 'conditions[stock]', 
			'label' => __('Product Stock'), 
			'title' => __('Product Stock'), 
			'required' => true, 
			'values'    => [
					'0' => __('All'), 
					'1' => __('In Stock'), 
					'2' => __('Out of Stock'), 
				 ],'value'=> $stock,
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
			$form->setValues($model->getData());
		}
		
        $this->setForm($form);
        return parent::_prepareForm();
    }
	public function isJSON($string){
		return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
	}
	public function sortByValue($a, $b) {
    return $a['value'] - $b['value'];
	}
}

