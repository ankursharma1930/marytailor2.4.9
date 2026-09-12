define([
	"jquery",
	"jquery/ui",
	],
function (jQuery) {
	  "use strict";
		jQuery.widget('Productfeed.csvcontent', {
			_create: function() {
				console.log('_create'); 	
				jQuery(".column-add").click(function() {
					jQuery("#Column_container").append(jQuery("#Column_template").html());
				});
				
				jQuery('#csv_options').on('click', '.column-delete', function() {
					jQuery(this).closest(".data-row").next('.row-format').remove();
					jQuery(this).closest(".data-row").remove();
				});
				jQuery('#csv_options').on('click', '.value-setting', function() {
					
					var current_tr = jQuery(this).parent().parent();
					var next_tr = jQuery(current_tr).next();
					if (next_tr.is(':visible')){
						console.log('is visible');
						jQuery(next_tr).hide();
						return;
					}
					if (next_tr.is(':hidden')){
						console.log('is hidden');
						jQuery(next_tr).show();
					}
				});
	
				jQuery('#csv_options').on('change', '.column-type', function() {
					
					var nextTD = jQuery(this).closest("td");
					
					jQuery(nextTD.next("td")).find('select').each(function(){
						jQuery(this).hide();
					});
					jQuery(nextTD.next("td")).find('input').each(function(){
						jQuery(this).hide();
					});
					var id = jQuery(nextTD.next("td")).find('#insert_attr_'+this.value).show();
					var tr = jQuery(this).closest('tr');
					var attribute_td = nextTD.next("td");
					switch(this.value){
					case "images":
							jQuery(attribute_td.next("td").next("td")).find('#insert_image_format').show();
							jQuery(attribute_td.next("td").next("td")).find('#insert_format').hide();
							break;
						default:
							jQuery(attribute_td.next("td").next("td")).find('#insert_image_format').hide();
							jQuery(attribute_td.next("td").next("td")).find('#insert_format').show();
							break;
						}
					});
				},
			_init: function () {
				this._load();
			},
			_load: function(){
				console.log('_load'); 	
				jQuery('#csv_options').find('.column-type').each(function(){
					console.log('attribute_td');
					var nextTD = jQuery(this).closest("td");
					
					jQuery(nextTD.next("td")).find('select').each(function(){
						jQuery(this).hide();
					});
					jQuery(nextTD.next("td")).find('input').each(function(){
						jQuery(this).hide();
					});
					var id = jQuery(nextTD.next("td")).find('#insert_attr_'+this.value).show();
					var tr = jQuery(this).closest('tr');
					var attribute_td = nextTD.next("td");
					switch(this.value){
					case "images":
							jQuery(attribute_td.next("td").next("td")).find('#insert_image_format').show();
							jQuery(attribute_td.next("td").next("td")).find('#insert_format').hide();
							break;
						default:
							jQuery(attribute_td.next("td").next("td")).find('#insert_image_format').hide();
							jQuery(attribute_td.next("td").next("td")).find('#insert_format').show();
							break;
						}
					});
			},
			
});
 	return jQuery.Productfeed.csvcontent;
});