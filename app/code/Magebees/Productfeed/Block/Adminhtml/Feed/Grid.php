<?php

namespace Magebees\Productfeed\Block\Adminhtml\Feed;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_websiteFactory;
    protected $_systemStore;
    protected $_dir = null;
    protected $filesystem;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magebees\Productfeed\Model\Feed $feed,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        array $data = []
    ) {
        $this->_feed = $feed;
        $this->_websiteFactory = $websiteFactory;
        $this->_systemStore = $systemStore;
        $this->_dir = $dir;
		
         $this->_filesystem = $context->getFilesystem();
        parent::__construct($context, $backendHelper, $data);
    }
    
    protected function _construct()
    {
        parent::_construct();
        $this->setId('feedGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
       // $this->setUseAjax(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = $this->_feed->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
        
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('feed_ids');
        
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
        
        
        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'storeview',
                [
                    'header' => __('Store View'),
                    'sortable' => false,
                    'index' => 'storeview',
                    'type' => 'options',
                    'options' => $this->_systemStore->getStoreOptionHash(),
                    'header_css_class' => 'col-websites',
                    'column_css_class' => 'col-websites'
                ]
            );
        }
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
            'download_url',
            [
            'header' => __('Download Feed'),
            'index' => 'download_url',
            'renderer'  => 'Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Renderer\Info',
            'filter' => false,'sortable' => false,
            ]
        );
        $this->addColumn(
            'lastgeneratedate',
            [
                'header' => __('Last Generated At'),
                'type' => 'datetime',
                'index' => 'lastgeneratedate',
                
            ]
        );
        $this->addColumn(
            'feedstatus',
            [
                'header' => __('Status'),
                'index' => 'feedstatus',
                'frame_callback' => [$this, 'isUpdated'],
                 'filter' => false,'sortable' => false,
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
                     ], [
                     'caption' =>__('Dublicate'),
                     'url' =>  [
                     'base' => '*/*/dublicate'
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
    public function isUpdated($value, $row, $column, $isExport)
    {
        $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        
		$scope_info = $this->_scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
		
		$path = $reader->getAbsolutePath($file_path);
        $download_url_info = $row->getDownloadUrl();
        if ($this->isJSON($download_url_info)) {
            $download_url_info = json_decode($download_url_info, true);
            if (isset($download_url_info['feedstatus'])) {
                if ($download_url_info['feedstatus']==1) {
                    if (file_exists($path.$download_url_info['feedname'])) {
                        $cell = '<span class="grid-severity-notice"><span>READY</span></span>';
                        return $cell;
                    } else {
                        $cell = '<span class="grid-severity-major"><span>NOT READY</span></span>';
                        return $cell;
                    }
                }
            }
        }
                    $cell = '<span class="grid-severity-major"><span>NOT READY</span></span>';
                    return $cell;
    }
    public function isJSON($string)
    {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/edit',
            ['id' => $row->getId()]
        );
    }
}
