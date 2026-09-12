<?php
namespace Magebees\Productfeed\Block\Adminhtml\Mapping;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magebees\Productfeed\Model\Mapping $mapping,
        array $data = []
    ) {
        $this->_mapping = $mapping;
        parent::__construct($context, $backendHelper, $data);
    }
    
    protected function _construct()
    {
        parent::_construct();
        $this->setId('mappingGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
      
    }
    
    protected function _prepareCollection()
    {
        $collection = $this->_mapping->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
        
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('mapping_ids');
        
        $this->getMassactionBlock()->addItem(
            'display',
            [
                        'label' => __('Delete'),
                        'url' => $this->getUrl('*/*/massdelete'),
                        'confirm' => __('Are you sure?'),
                        'selected'=>true
                ]
        );
        return $this;
    }
        
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
            ]
        );
        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
               'width' => '100',
                'type' => 'action',
                'getter' => 'getId',
                'actions' =>  [
                     [
                    'caption' =>__(' '),
                    'url' =>  [
                    'base' => '*/*/'
                    ]
                     ],
                     [
                    'caption' =>__('Edit'),
                    'url' =>  [
                    'base' => '*/*/edit'
                    ],
                    'field' => 'id'
                     ],
                     [
                     'caption' =>__('Delete'),
                     'url' =>  [
                     'base' => '*/*/delete'
                     ],
                     'field' => 'id'
                     ]
                    
                ],
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true
            ]
        );
    

        return parent::_prepareColumns();
    }
    
    /**
     * @return string
     */
    
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/edit',
            ['id' => $row->getId()]
        );
    }
}
