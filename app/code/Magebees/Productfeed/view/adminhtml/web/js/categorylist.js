define([
	"jquery",
	"jquery/ui",
	
	],

function (jQuery) {
	  "use strict";
		jQuery.widget('Productfeed.categorylist', {
			_create: function() {
				console.log('_create123456');
				var loadingimageurl = this.options.loading_image_url;
				var itemtype = jQuery("#item_type").val();
				console.log('itemtype:::'+itemtype);
				if(itemtype=='xml'){
					jQuery("#magebees_productfeed_feed_edit_tabs_feed_xml_section").show();
					jQuery("#magebees_productfeed_feed_edit_tabs_feed_csv_section").hide();
				}else if(itemtype=='csv'){
					jQuery("#magebees_productfeed_feed_edit_tabs_feed_xml_section").hide();
					jQuery("#magebees_productfeed_feed_edit_tabs_feed_csv_section").show();
				}
				
				jQuery("#item_type").change(function() {
					if(this.value=='xml'){
							jQuery("#magebees_productfeed_feed_edit_tabs_feed_csv_section").hide();
							jQuery("#magebees_productfeed_feed_edit_tabs_feed_xml_section").show();
					}else if(this.value=='csv'){
							jQuery("#magebees_productfeed_feed_edit_tabs_feed_xml_section").hide();
							jQuery("#magebees_productfeed_feed_edit_tabs_feed_csv_section").show();
					}
				});
				var self = this;
				console.log('_create categorylist'); 	
				var category_url = this.options.categorylist_url;
				var store_id = this.options.current_store;
				var feed_id = this.options.feed_id;
				
				//
				jQuery('#item_storeview').on('change',function() {
					this.options.feed_id = this.value;
					self.get_categorymapping_demo();
					});
				
				this.get_categorymapping_demo();
				},
			_init: function () {
				this._load();
				
				console.log('_init categorylist');
        	},
			_load: function(){
				console.log('load categorylist');
			},
			content_type:function(){
				
			},
			get_categorymapping_demo:function()
			{
				
				var loadingimageurl = this.options.loading_image_url;
				jQuery("#cat_content").html("<img id='cat-processicon' src='"+loadingimageurl+"'/>");
				var self = this;
				console.log("mapping_url"+this.options.categorylist_url);
				console.log("currentmapping_id"+this.options.currentmapping_id);
				if(this.options.feed_id!=""){
					jQuery.ajax({
						url : this.options.categorylist_url,
						data: { is_ajax: "1", feed_id:this.options.feed_id},
						type: 'GET',
						success: function(data){
							jQuery('#cat_content').html(data);
							self.mapping_int();
						}
						});	
				}else{
					jQuery.ajax({
						url : this.options.categorylist_url,
						data: { is_ajax: "1"},
						type: 'GET',
						success: function(data){
							jQuery('#cat_content').html(data);
							self.mapping_int();
						}
						});	
				}
				
			},
			mapping_int:function(){
				 var self = this;
				
				jQuery('.category-mapping .toggle').each(function(item) {
					
					jQuery(this).click(function(event){
						
					self.toggleCategories(this,event.currentTarget);
					});
        });
			},
			toggleCategories: function(item, type)
			{
				
				var self = this;
				   if (jQuery(item).hasClass('open')) {
					   type = 'show';
				   } else {
					   type = 'hide';
				   }
				var input  = jQuery(item).parent().find('.mapping-checkbox').first();
				
				var id     = jQuery(input).attr('data-id');
				
				jQuery('input[type=checkbox][data-parent="'+id+'"]').each(function () {
				
					if (type == 'hide') {
						var toggle = jQuery(this).parent().parent().find('.toggle').first();
						if (jQuery(toggle).hasClass('close')) {
							self.toggleCategories(toggle, type);
						}
						jQuery(this).parent().parent().hide();
					}
					else {
						jQuery(this).parent().parent().show();
				
					}
				});
				if (type == 'show') {
					jQuery(item).addClass('close').removeClass('open');
					
				} else {
					jQuery(item).addClass('open').removeClass('close');
				}
			},
		
});
 	return jQuery.Productfeed.categorylist;
});