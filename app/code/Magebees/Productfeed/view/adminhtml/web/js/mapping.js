define([
	"jquery",
	"jquery/ui",
	
	],

function (jQuery) {
	  "use strict";
		jQuery.widget('Productfeed.mapping', {
			_create: function() {
				console.log('_create'); 	
				var mapping_url = this.options.mapping_url;
				var loading_url = this.options.loading_url;
				
				var currentmapping_id = this.options.currentmapping_id;
				this.get_categorymapping_demo();
				
				},
			_init: function () {
				this._load();
				
				console.log('_init');
        	},
			_load: function(){
				console.log('load');
			},
			
			get_categorymapping_demo:function()
			{	
				jQuery('#cat_mapping').html('<img id="processicon" src="'+this.options.loading_url+'" />');
				var self = this;
				console.log("mapping_url"+this.options.mapping_url);
				console.log("currentmapping_id"+this.options.currentmapping_id);
				if(this.options.currentmapping_id!=""){
					jQuery.ajax({
						url : this.options.mapping_url,
						data: { is_ajax: "1", mapping_id:this.options.currentmapping_id},
						type: 'GET',
						success: function(data){
							jQuery('#cat_mapping').html(data);
							self.mapping_int();
						}
						});	
				}else{
					jQuery.ajax({
						url : this.options.mapping_url,
						data: { is_ajax: "1"},
						type: 'GET',
						success: function(data){
							jQuery('#cat_mapping').html(data);
							self.mapping_int();
						}
						});	
				}
			},
			mapping_int:function(){
				 var self = this;
				jQuery('.mapping-input').each(function(item) {
					jQuery(this).keyup(function(event){
						var input = event.currentTarget;
						console.log('input');
						console.log(input);
						self.applyPlaceholder(input);
					});
				});
				jQuery('.category-mapping .toggle').each(function(item) {
					jQuery(this).click(function(event){
					self.toggleCategories(this,event.currentTarget);
					var input  = jQuery(item).parent().find('.mapping-input').first();
					self.applyPlaceholder(input);
				});
				self.applyPlaceholder(this);
        });
			},
			applyPlaceholder: function(input)
			{ 
				var self = this;

				if (input.value === '') {
					var value = self.getParentValue(input);
					console.log('value::'+value);
					if (value !== '') {
					 jQuery(input).attr('placeholder',value);
					 //jQuery(input).attr('value',value);
					} else {
						jQuery(input).attr('placeholder','');
						//jQuery(input).attr('value','');
					}
				}

				var id = jQuery(input).attr('data-id'); 
				jQuery('input[data-parent="'+id+'"]').each(function() {
					self.applyPlaceholder(this);
				});
			},
			getParentValue: function(input)
			{
				var self        = this;
				var parentId    = jQuery(input).attr('data-parent');  
				var parentInput = jQuery('[data-id="' + parentId + '"]').first();
				if (parentInput === undefined) {
					return '';
				}

				var val = jQuery(parentInput).val();
				if (val) {
					return val;
				} else {
						return jQuery(parentInput).attr('placeholder');  
				}
			},
			toggleCategories: function(item, type)
			{
				var self = this;
				   if (jQuery(item).hasClass('open')) {
					   type = 'show';
				   } else {
					   type = 'hide';
				   }
				var input  = jQuery(item).parent().find('.mapping-input').first();
				
				var id     = jQuery(input).attr('data-id');
				jQuery('input[data-parent="'+id+'"]').each(function() {
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
					self.applyPlaceholder(input);
				} else {
					jQuery(item).addClass('open').removeClass('close');
				}
			},
		
});
 	return jQuery.Productfeed.mapping;
});
