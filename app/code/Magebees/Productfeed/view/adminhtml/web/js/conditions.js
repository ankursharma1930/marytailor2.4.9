define([
	"jquery",
	"jquery/ui",
	
	],

function (jQuery) {
	  "use strict";
		jQuery.widget('Productfeed.conditions', {
			_create: function() {
				console.log('_create conditions'); 
				var or_count = parseInt(this.options.max_or_key);
				var and_count = parseInt(this.options.max_and_key);
				var condition_url = this.options.condition_url;
				var attribute_url = this.options.attribute_url;
				var condition_id = this.options.condition_id;
				
				
		
				
				//var mapping_url = this.options.mapping_url;
				//var loadimageurl = this.options.loadimageurl;
				
				//var currentmapping_id = this.options.currentmapping_id;
				
				
				this.getConditionsList(or_count,and_count,attribute_url);
				
				},
			_init: function () {
				this._load();
				
				console.log('_init');
        	},
			_load: function(){
				console.log('load');
			},
			getConditionsList: function(or_count,and_count,attribute_url){
				jQuery('#condition_mapping').html('<img id="processicon" src="'+this.options.loading_url+'" />');
				
				
				console.log('getConditionsList'+this.options.condition_url);
				
				var self = this;
				console.log("condition_url"+this.options.condition_url);
				console.log("condition_id"+this.options.condition_id);
				if(this.options.condition_id!=""){
					
					jQuery.ajax({
						url : this.options.condition_url,
						data: { /*is_ajax: "1",*/ condition_id:this.options.condition_id},
						type: 'GET',
						success: function(data){
							jQuery('#condition_mapping').html(data);
							self.buttonClick(or_count,and_count,attribute_url);
							jQuery('body').trigger('processStop');
						}
						});	
				}else{
					jQuery.ajax({
						url : this.options.condition_url,
						//data: { is_ajax: "1"},
						type: 'GET',
						success: function(data){
							jQuery('#condition_mapping').html(data);
							self.buttonClick(or_count,and_count,attribute_url);
							jQuery('body').trigger('processStop');
						}
						});	
				}
			},
			buttonClick: function(or_count,and_count,attribute_url){
				
				
				jQuery('#conditions_options').on('click', '.add-or-conditions', function() {
					or_count = or_count+1;
					
					var dropdown = jQuery("#Conditions_template").html();
					
					// dynamic_conditions
					var button_or_condition = "<tr id=or-condition-"+or_count+"><td class='icons'><button title='Add AND Condition' type='button' condition_count="+or_count+" id=and-condition-"+or_count+" class='icoBtn add-and-conditions'><i class='fa fa-plus-circle' aria-hidden='true'></i></button></td><td colspan='3'><label for='condition-"+or_count+"-result-value'>Result</label><input type='text' class='input-text admin__control-text' id=condition-"+or_count+"-result-value name='condition["+or_count+"][result][value]'></td><td class='icons'><button condition_count="+or_count+" title='Delete Or Condition' type='button' id='condition-"+or_count+"-or-delete' class='icoBtn delete delete-or-conditions'><i class='fa fa-trash' aria-hidden='true'></i></button></td></tr>";
					
					jQuery("#dynamic_conditions").find('tbody').append(button_or_condition);
					
					//jQuery("#Conditions_container").append(jQuery("#Conditions_template").html());
					
				});
					jQuery('#conditions_options').on('click', '.delete-or-conditions', function() {
						
						
					//jQuery(this).closest(".field-row").remove();
					var cur_tr_id = jQuery(this).parent().parent().attr('id');
					jQuery("[id^='"+cur_tr_id+"']").remove();
				});
				jQuery('#conditions_options').on('click', '.delete-and-conditions', function() {
					jQuery(this).parent().parent().remove();
					
				});
				
				
				
				jQuery('#conditions_options').on('click', '.add-and-conditions', function() {
					and_count = and_count+1;
					var btn_id = jQuery(this).attr('id');
					var condition_count = jQuery(this).attr('condition_count');
					var cur_tr = jQuery(this).parent().parent();
					var current_row_id = jQuery(cur_tr).attr('id');
					console.log("current_row_id::"+current_row_id);
					console.log("btn_id::"+btn_id);
					var insert_attr_attribute = jQuery("#insert_attr_attribute" ).html();
					var condition_operator = jQuery("#condition_operator" ).html();
					
					
					
					var button_and_condition = "<tr id="+current_row_id+"-and-condition-"+and_count+"><td></td><td> <select class='select admin__control-select insert_attr_attribute' id='insert_attr_attribute_"+and_count+"' name='condition["+condition_count+"][attribute]["+and_count+"][]' and_counting="+and_count+" or_counting="+condition_count+">"+insert_attr_attribute+"</select></td><td> <select id='insert_condition_operator_"+and_count+"' class='select admin__control-select' name='condition["+condition_count+"][operator]["+and_count+"][]' and="+and_count+" or="+condition_count+">"+condition_operator+"</select></td> <td id=condition-"+and_count+"-value><input type='text' class='input-text admin__control-text' id=condition-"+condition_count+"-result-value-"+and_count+" name='condition["+condition_count+"][value]["+and_count+"][]'></td><td class='icons'><button title='Delete AND Condition' count="+and_count+" type='button' id='condition-"+and_count+"-or-delete' class='icoBtn delete delete-and-conditions'><i class='fa fa-trash' aria-hidden='true'></i></button></td></tr>";
					jQuery("#"+current_row_id).after(button_and_condition);
			
					console.log("add-and-conditions done::");
					
				});
				jQuery('#conditions_options').on('change', '.insert_attr_attribute', function() {
					console.log(jQuery(this).attr('id'));
					
					var option_and_counting = jQuery(this).attr('and_counting');
					var option_or_counting = jQuery(this).attr('or_counting');
					var code = jQuery(this).val();
					jQuery.ajax({
						url : attribute_url,
						data: { attribute_code: code,and_counting:option_and_counting,or_counting:option_or_counting},
						type: 'GET',
						showLoader:true,
						success: function(data){
							
							console.log("attribute_options::"+data.attribute_options);
							console.log("operators::"+data.operators);
							console.log("option_and_counting"+option_and_counting);
							console.log("option_or_counting"+option_or_counting);
							jQuery("#insert_condition_operator_"+option_and_counting).html(data.operators);
							jQuery("#condition-"+option_and_counting+"-value").html(data.attribute_options);
							
						
						}
						});	
					
				});
				
			}
			
});
 	return jQuery.Productfeed.conditions;
});
