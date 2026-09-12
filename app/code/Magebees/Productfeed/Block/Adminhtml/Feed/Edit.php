<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Block\Adminhtml\Feed;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_feed';
        $this->_blockGroup = 'Magebees_Productfeed';

        parent::_construct();
        //$this->buttonList->update('save', 'onclick', 'submitForm()');
        
        $this->buttonList->add(
            'save_and_continue_edit',
            [
                'class' => 'save',
                'label' => __('Save and Continue Edit'),
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ],
            10
        );
        $feed = $this->_coreRegistry->registry('current_magebees_productfeed_feed');
        if ($feed->getId()) {
            $this->addButton(
                'delete',
                [
                'label' => __('Delete'),
                'onclick' => 'deleteConfirm(' . json_encode(__('Are you sure you want to do this?'))
                    . ','
                    . json_encode($this->getDeleteUrl())
                    . ')',
                'class' => 'save',
                'level' => -1
                ]
            );
	
		if($feed->getContent()){
			if($feed->getType()=='xml') {		
				$feed_content = json_decode($feed->getContent(),true);
				if($feed_content['xml_body'])
				{
				$this->addButton(
						'export',
						[
						'label' => __('Generate Feed'),
						'class' => 'export-feed',
						'level' => -1
						]
					);
			}

			}else{
			$this->addButton(
                'export',
                [
                'label' => __('Generate Feed'),
                'class' => 'export-feed',
                'level' => -1
                ]
            );
			}
			
		}

	
            
        }
        
        if ($feed->getId()) {
            $current_feed_id = $feed->getId();
            $feed_type = $feed->getType();
			$current_store = $feed->getStoreview();
        } else {
            $current_feed_id = "";
            $feed_type = "";
			$current_store = "";
		}
        $feed = $this->_coreRegistry->registry('current_magebees_productfeed_feed');
        if ($feed->getId()) {
            $export_feed = $this->getUrl('magebees_productfeed/feed/exportrun', ['feed_id' => $feed->getId()]);
            $feed_name = $feed->getName();
            $current_feed_url = $this->getUrl('magebees_productfeed/feed/edit', ['id' => $feed->getId()]);
        } else {
            $export_feed = $this->getUrl('magebees_productfeed/feed/exportrun');
            $current_feed_url = "";
            $feed_name = "";
        }
        
        $merge_csv = $this->getUrl('magebees_productfeed/feed/mergecsv');
        $export_count = $this->getUrl('*/*/Exportrecordcount');
		$testconnection = $this->getUrl('magebees_productfeed/feed/testconnection'); 
        $loadimageurl = $this->getViewFileUrl('Magebees_Productfeed::images/loading.gif');
        $category_list = $this->getUrl('*/*/CategoryList');
        //$this->buttonList->update('save_and_continue_edit', 'onclick', 'submitForm()');
        $this->_formScripts[] = "
		 	requirejs(['jquery','domReady!','categorylist'], function(jQuery){
				var store_id = jQuery('#item_storeview').val();
				jQuery('#cat_content').categorylist({
					categorylist_url : '".$category_list."',
					feed_id : '".$current_feed_id."',
					loading_image_url : '".$loadimageurl."',
					current_store : store_id,
				});
			});
			requirejs(['jquery','domReady!'], function(jQuery){
				
				var ftp_enable_disable = jQuery('#item_enable_ftp_upload').val();
					if(ftp_enable_disable==1)
					{
					jQuery('.field-connection').show();
					}else if(ftp_enable_disable==0){
					jQuery('.field-connection').hide();
					}
					
				jQuery('#item_enable_ftp_upload').change(function(e){
					var ftp_enable_disable = jQuery(this).val();
					if(ftp_enable_disable==1)
					{
					jQuery('.field-connection').show();
					}else if(ftp_enable_disable==0){
					jQuery('.field-connection').hide();
					
					}
					
				});
				
				jQuery('.testconnection').click(function(e){
					var enable_ftp_upload = jQuery('#item_enable_ftp_upload').val();
					var protocol = jQuery('#item_protocol').val();
					var host = jQuery('#item_host').val();
					var username = jQuery('#item_username').val();
					var password = jQuery('#item_password').val();
					var use_active_mode = jQuery('#item_use_active_mode').val();
					var directory_path = jQuery('#item_directory_path').val();
					
					if(enable_ftp_upload==1){
						jQuery('#show_loader').attr('src', '".$loadimageurl."');
						jQuery('#show_loader').show();
						jQuery.ajax({
						url : '".$testconnection."',
						data: { protocol : protocol,host: host,username: username,
						password: password,use_active_mode: use_active_mode, 							directory_path: directory_path},
						type: 'POST',
						success: function(data){
							jQuery('#show_loader').hide();
							if(data.connection_status==1)
							{
							jQuery('.message-success').html(data.message);
							jQuery('.message-success').show();
							jQuery('.message-error').hide();
							}else if(data.connection_status==0){
							jQuery('.message-error').html(data.message);
							jQuery('.message-error').show();
							jQuery('.message-success').hide();
							}
						}
						});	
						
					}
				});
				
				
				
			});
			
			
			requirejs(['jquery','domReady!','exportfeed'], function(jQuery){
				jQuery('#export').exportfeed({
					loading_image_url : '".$loadimageurl."',
					export_count_url : '".$export_count."',
					export_feed_url : '".$export_feed."',
					merge_feed_url : '".$merge_csv."',
					current_feed_url : '".$current_feed_url."',
					feed_id : '".$current_feed_id."',
					feed_type :'".$feed_type."',
					current_store :'".$current_store."',
					
				});
			});
			require(['jquery','Magento_Ui/js/modal/modal'],function(jQuery,modal) {
			var options = {
			type: 'popup',
			responsive: true,
			innerScroll: true,
			title: '".$feed_name."',
			buttons: [{
				text: jQuery.mage.__('Close'),
				class: '',
				click: function () {

					this.closeModal();
					location.reload(); 

				}

			}]
			};
			var popup = modal(options, jQuery('#export_popup'));
			
		}); 
			";
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $feed = $this->_coreRegistry->registry('current_magebees_productfeed_feed');
        if ($feed->getId()) {
            return __("Edit Feed '%1'", $this->escapeHtml($feed->getName()));
        } else {
            return __('New Feed');
        }
    }
    public function getValidationUrl()
    {
      //return $this->getUrl('*/*/Validate');
	  
    }
    public function getDeleteUrl(array $args = [])
    {
        
        $feed = $this->_coreRegistry->registry('current_magebees_productfeed_feed');
        if ($feed->getId()) {
            return $this->getUrl('magebees_productfeed/feed/delete', ['id' => $feed->getId()]);
        }
    }
    public function getGenerateFeedUrl(array $args = [])
    {
        
        $feed = $this->_coreRegistry->registry('current_magebees_productfeed_feed');
        if ($feed->getId()) {
            return $this->getUrl('magebees_productfeed/feed/exportcsv', ['id' => $feed->getId()]);
        }
    }
    public function getGenerateProductUrl(array $args = [])
    {
        
        $feed = $this->_coreRegistry->registry('current_magebees_productfeed_feed');
        if ($feed->getId()) {
            return $this->getUrl('magebees_productfeed/feed/exportproduct', ['id' => $feed->getId()]);
        }
    }
}
