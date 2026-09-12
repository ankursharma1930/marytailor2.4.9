<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml;

class Feed extends \Magento\Backend\Block\Widget\Grid\Container
{
    
      /**
       * @var \Magento\Catalog\Model\Product\TypeFactory
       */
    protected $_typeFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Catalog\Model\Product\TypeFactory $typeFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Catalog\Model\Product\TypeFactory $typeFactory,
        \Magebees\Productfeed\Model\Templates $templates,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_typeFactory = $typeFactory;
        $this->_templates = $templates;
        parent::__construct($context, $data);
    }
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_feed';
        $this->_blockGroup = 'Magebees_Productfeed';
        $this->_headerText = __('Feed');
       // $this->_addButtonLabel = __('Add New Feed');
       
        parent::_construct();
        $this->removeButton('add');
    }
    protected function _prepareLayout()
    {
         
         $addButtonProps = [
            'id' => 'add_new_feed',
            'label' => __('Add Feed By Template'),
            'class' => 'add',
            'button_class' => '',
            'class_name' => 'Magento\Backend\Block\Widget\Button\SplitButton',
            'options' => $this->_getAddFeedButtonOptions(),
         ];
         $this->buttonList->add('add_new', $addButtonProps);
                            
         return parent::_prepareLayout();
    }
     /**
      * Retrieve options for 'Add Product' split button
      *
      * @return array
      */
    protected function _getAddFeedButtonOptions()
    {
        $splitButtonOptions = [];
        $types = $this->getTemplateList();
          
       
        foreach ($types as $typeId => $type) {
            $splitButtonOptions[$typeId] = [
                'label' => __($type['label']),
                'onclick' => "setLocation('" . $this->_getProductCreateUrl($typeId) . "')",
            ];
        }

        return $splitButtonOptions;
    }

    /**
     * Retrieve product create url by specified product type
     *
     * @param string $type
     * @return string
     */
    protected function _getProductCreateUrl($type)
    {
        return $this->getUrl(
            'magebees_productfeed/feed/new',
            ['template' => $type]
        );
    }

    /**
     * Check whether it is single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->_storeManager->isSingleStoreMode();
    }
    public function getTemplateList()
    {
        
        $templates = $this->_templates->getCollection();
        if (count($templates)>0) {
            $feedtemplate[] = ["value" => "0", "label" => __("Blank Template")];
            foreach ($templates as $template) :
                $template_name = $template['name']." [".$template['type']."] ";
                $feedtemplate[$template['id']] = ["value" => $template['id'], "label" => __($template_name)];
            endforeach;
            ksort($feedtemplate);
            return $feedtemplate;
        } else {
            $feedtemplate[] = ["value" => "0", "label" => __("Blank Template")];
            ksort($feedtemplate);
            return $feedtemplate;
        }
    }
}
