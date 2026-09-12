<?php

namespace Magebees\Productfeed\Block\Adminhtml\Templates;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magebees\Productfeed\Model\Templates $templates,
        array $data = []
    ) {
        $this->_templates = $templates;
        parent::__construct($context, $backendHelper, $data);
    }
    
    protected function _construct()
    {
        parent::_construct();
        $this->setId('templatesGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
      
    }
    
    protected function _prepareCollection()
    {
        $collection = $this->_templates->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
        
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('template_ids');
        
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
                'width' => '80px',
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
             'type',
             [
                'header' => __('Type'),
                'align' => 'left',
                    'width' => '80px',
                    'index' => 'type',
                    'type' => 'options',
                    'options' =>  [
                            'xml' => 'XML',
                            'csv' => 'CSV'
                    ]
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
                     ],  [
                    'caption' =>__('Edit'),
                    'url' =>  [
                    'base' => '*/*/edit'
                    ],
                    'field' => 'id'
                     ],
                     [
                     'caption' =>__('Dublicate'),
                     'url' =>  [
                     'base' => '*/*/dublicate'
                     ],
                     'field' => 'id'
                     ],
                     [
                     'caption' =>__('Export Template'),
                     'url' =>  [
                     'base' => '*/*/exporttemplate'
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
