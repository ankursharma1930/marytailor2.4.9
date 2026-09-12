<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml;

class Templates extends \Magento\Backend\Block\Widget\Grid\Container
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
        \Magento\Catalog\Model\ProductFactory $productFactory,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_typeFactory = $typeFactory;

        parent::__construct($context, $data);
    }
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_templates';
        $this->_blockGroup = 'Magebees_Productfeed';
        $this->_headerText = __('Templates');
        $this->_addButtonLabel = __('Add New Templates');
        
        $this->addButton('import_templates', [
            'label' => 'Import Templates',
            'onclick' => "setLocation('".$this->getUrl('magebees_productfeed/import')."')",
        ]);
        parent::_construct();
    }
}
