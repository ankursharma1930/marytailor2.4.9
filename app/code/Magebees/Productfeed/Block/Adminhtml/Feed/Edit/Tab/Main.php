<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

// @codingStandardsIgnoreFile

namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab;


use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{

    /**
     * {@inheritdoc}
     */
	protected $_templates;
	protected $_systemStore;
	protected $_yesno;
	protected $_objectManager;
	protected $_filesystem;
	
	 public function __construct(
		\Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
		\Magento\Config\Model\Config\Source\Yesno $yesno,
		\Magebees\Productfeed\Model\Templates $templates,
		\Magebees\Productfeed\Model\Feed $feed,
		array $data = array()
    ) {
        $this->_systemStore = $systemStore;
		$this->_filesystem = $context->getFilesystem();
		$this->_templates = $templates;
		$this->_yesno = $yesno;
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
        $model = $this->_coreRegistry->registry('current_magebees_productfeed_feed');
		$isElementDisabled = false;
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Feed General Information')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
			$download_url_info = $model->getDownloadUrl();
			$informations = $model->getData();
			
			if($this->isJSON($download_url_info)){
				$download_url_info = json_decode($download_url_info, true);
				foreach($download_url_info as $subkey => $subvalue):
						$informations[$subkey] = $subvalue;	
					endforeach;
			}else{
				$informations[$key] = $value;	
			}
	   }else{
			$informations = array();
			$template_id = $this->getRequest()->getParam('template');
			
			$this->_templates->load($template_id);
			$informations['type'] = $this->_templates->getType();
			$informations['name'] = $this->_templates->getName();
			$informations['feedname'] = $this->_templates->getName();
			
			
		}
        
		$fieldset->addField(
            'name',
            'text',
			['name' => 'name', 
			'label' => __('Name'),
			'title' => __('Name'),
			'required' => true, 
			]
        );
		
		
		$fieldset->addField(
            'type',
            'select',
			['name' => 'type', 
			'label' => __('Type'), 
			'title' => __('Type'), 
			'required' => true, 
			'values'=> $this->feed->getTemplateType()]
        );
		
		
		 $fieldset->addField(
       'storeview',
       'select',
       [
         'name'     => 'storeview[]',
         'label'    => __('Store Views'),
         'title'    => __('Store Views'),
         'required' => true,
         'values'   => $this->_systemStore->getStoreValuesForForm(false, false),
       ]
    );
		$fieldset->addField(
            'feedname',
            'text',
			['name' => 'feedname', 
			'label' => __('Feed Name'),
			'title' => __('Feed Name'),
			'required' => true, 
			]
        );
	
		if($model->getDownloadUrl()!=""){
			
		$file_info = json_decode($model->getDownloadUrl(),true);
			
		if(!empty($file_info['feedname'])&&(isset($file_info['feedstatus']))){
			if($file_info['feedstatus']!="0") {
			
			
			$feedname = $file_info['feedname'];
			$baseUrl = $this->getUrl('*/*/downloadexportedfile',array('file'=>$feedname));
		
		$extvardir = $this->_filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
		$scope_info = $this->_scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
			
		//$exportdir = '/export/'.$feedname;
			$exportdir = $file_path.$feedname;
		
		//$front_url = $this->_storeManager->getStore()->getBaseUrl().'var/export/'.$feedname;
			$front_url = $this->_storeManager->getStore()->getBaseUrl().$file_path.$feedname;
		$date=date_create($model->getLastgeneratedate());
		
		
			$reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
			$path = $reader->getAbsolutePath($file_path);
			if(file_exists($path.$feedname)){
				
				$value = "<div class=control-value admin__field-value'><table class='admin__table-secondary'><tbody><tr><th>Feed Access Url</th><td><a href=".$baseUrl."title=".$model->getName().">".$front_url."</a></td></tr><tr><th>Product Count </th><td>".$file_info['total_products']."</td></tr><tr><th>Last Generated</th><td>".date_format($date,"M d Y H:i:s")."</td></tr></table></div>";
			}
			else{
				$value = "<div class=control-value admin__field-value'><table class='admin__table-secondary'><tbody><tr><th>Feed Access Url</th><td>File Not Exists</td></tr></table></div>";
			}
			$fieldset->addField('download','label',
					[
						'name' => 'download',
						'label' => __(''),
						'title' => __(''),
						'after_element_html' => $value,
					]);
		
			
		}
		}
		}
		
	/* Code Start For Set Custom Div In the form*/
	
	$fieldset->addType(
        'loading_process',
        '\Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Renderer\Loading'
    );
    $fieldset->addField(
        'loading',
        'loading_process',
        [
            'name'  => 'loading',
            'label' => __(''),
            'title' => __(''),
           
        ]
    ); 
	if (!$model->getId()) {
		$form->setValues($informations);
	}else{
		//$form->setValues($model->getData());
		$form->setValues($informations);
	}
	   // $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
	public function isJSON($string){
			return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
		}
}

