define([
	"jquery",
	"mage/template","jquery/ui","mage/translate","Magento_Ui/js/modal/modal",
	],
	
function (jQuery) {
	  "use strict";
	  var response = "";
				var countOfStartedProfiles = 0;
				var countOfUpdated = 0;
				var countOfError = 0;
				var totalRecords = 0;
				var totalPage=0;
		jQuery.widget('Productfeed.exportfeed', {
			_create: function() {
				var self = this;
				
				console.log('_create exportfeed'); 	
				var loadingimageurl = this.options.loading_image_url;
				var exporturl = this.options.export_feed_url;
				var export_count_url = this.options.export_count_url;
				var feed_type = this.options.feed_type;
				var store_id = this.options.current_store;
				var feed_id = this.options.feed_id;
				
				var export_type='*';
				
				jQuery(".action-close").click(function() {
					location.reload(); 
				});
				
				
				jQuery("#export").click(function() {
					
					jQuery("#loading-icon").html("<img id='loadingicon' src='"+loadingimageurl+"'/>");

					jQuery('#export_popup').modal('openModal');
					
					jQuery('#export_popup1').html('Initialization');
					jQuery('#myProgress').hide();
					var response = "";
					jQuery.ajax({
							url : export_count_url,
							data: { 
									'store_id' :store_id,
									'feed_id' : feed_id,
									form_key: FORM_KEY,
								} ,
							dataType: 'json',
							type: 'post',
							//showLoader:true,
							success: function(data){
								try {
									response = data;
									console.log(response.export_can_proceed);
								}catch(e){
									alert("Error: getTotalProductsCount");
								}
								console.log(response);
								console.log(response.totalProduct);
								
								if(response.export_can_proceed==true){
								jQuery('.steps-export').show();
								//jQuery("#loading-icon").hide();
								totalRecords=response.totalProduct;
								jQuery("#total_products").addClass("active");
								jQuery("#export_popup1").html('Total: '+totalRecords+' Product(s) founds.');
								//self.exportCall();
								
								self.exportCall(response.splitExport,1,null,response.timestamp);
								} else if((response.export_can_proceed==false)&&(response.totalProduct!=0)){
									jQuery("#error_message").html(response.error_message);
									jQuery("#error_message").show();
									jQuery("#loading-icon").hide();
									jQuery("#export_popup1").hide();
								}else if((response.export_can_proceed==false)&&(response.totalProduct==0)){	
									jQuery("#error_message").html(response.error_message);
									jQuery("#error_message").show();
									jQuery("#loading-icon").hide();
									jQuery("#export_popup1").hide();
									
								}
							}
						});
						});
				
				},
			_init: function () {
				this._load();
				
				console.log('_init exportfeed');
        	},
			_load: function(){
				console.log('load exportfeed');
			},
			exportCall:function(splitRun,page,filename,feedname,timestamp){
				var self = this;
				/*console.log("splitRun"+splitRun);
				console.log("page"+page);
				console.log("filename"+filename);
				console.log("timestamp"+timestamp);*/
				var response;
				var exporturl = this.options.export_feed_url;
			  	
				jQuery.ajax({
					url : exporturl,
					data: { 
							'store_id' : this.options.current_store,
							'exportfiletype' : this.options.feed_type,
							'splitRun' : splitRun,
							'timestamp' : timestamp,
							'filename' : filename,
							'feedname' : feedname,
							'page' : page,
							'feed_id' :this.options.feed_id,
							form_key : FORM_KEY,
						} ,
					dataType: 'json',
					type: 'post',
					//showLoader:true,
					success: function(transport) {
						totalPage = totalPage + 1;
						
						try {
							response = transport;
						}catch(e){
							alert("Error: exportCall");
						}
						
						if(response.proceed_next==true){
							jQuery("#loading-icon").hide();
							countOfUpdated = countOfUpdated+response.exportedProducts;
							jQuery("#export_popup1").html('Exported '+countOfUpdated+' out of '+totalRecords+' Product(s).');
							jQuery("#label").html('Exported '+countOfUpdated+' Exported out of '+totalRecords+' Product(s).');
							jQuery("#total_products").removeClass("active");
							jQuery("#exporting").addClass("active");
							jQuery('#myProgress').show();
							self.fillProcessbar(self.getPercent(countOfUpdated,totalRecords));
							
							
							jQuery("#export_popup1").html('<br>Exported: '+self.getPercent(countOfUpdated,totalRecords)+'% '+countOfUpdated+'/'+totalRecords+' product(s).');
							jQuery('#myProgress').show();
							//move(getPercent());
							self.exportCall(splitRun,response.page,response.filename,response.feedname,response.timestamp);
						}else if((response.filename!='')&&(response.export_can_proceed!=false)) {
							/* Added Line */
							countOfUpdated = countOfUpdated+response.exportedProducts;
							jQuery("#export_popup1").html('Exported '+countOfUpdated+' out of '+totalRecords+' Product(s).');
							jQuery("#total_products").removeClass("active");
							jQuery("#exporting").addClass("active");
							jQuery('#myProgress').show();
							self.fillProcessbar(self.getPercent(countOfUpdated,totalRecords));
							/* Added Line */
							
							
							console.log("this.totalPage::"+totalPage);
							 self.mergingCSVFiles(response.filename,response.feedname,1,totalPage,response.timestamp);
							}else if(response.export_can_proceed==false){
										
									jQuery("#loading-icon").hide();
									jQuery(".nav-bar").hide();
									jQuery("#export_popup1").hide();
									jQuery("#error_message").html(response.error_message);
									jQuery("#error_message").show();
							}
						}
				});
			
			},
			mergingCSVFiles:function(filename,feedname,processPage,page,timestamp){
				
				var self = this;
				var response;
				var current_url = this.options.current_feed_url;
				jQuery.ajax({
					url : this.options.merge_feed_url,
					data: {
							'filename' : filename,
							'feedname' : feedname,
							'page' : page,
							'processPage' : processPage,
							'feed_id' :this.options.feed_id,
							'timestamp' : timestamp,
							form_key : FORM_KEY,
						} ,
					dataType: 'json',
					type: 'post',
					//showLoader:true,
					success: function(transport){
						try {
							response = transport;
						}catch(e){
							alert("Error: mergingCSVFiles");			
						}
						if(response.proceed_next==true){
							//jQuery("#exporting").removeClass("active");
							//jQuery("#complate").addClass("active");
							jQuery("#myProgress").hide();
							jQuery("#export_popup1").html("<br>Please wait while file is being prepared for download... ");
							self.mergingCSVFiles(response.filename,response.feedname,response.processPage,response.page,response.timestamp);
							self.fillProcessbar(self.getPercent(response.processPage,response.page));
						}else if(response.export_can_proceed!=false){
							jQuery("#export_popup1").html('File Exported Successfully');
							/* Added Line */
							jQuery("#exporting").removeClass("active");
							jQuery("#complate").addClass("active");
							/* Added Line */
							jQuery("#myProgress").hide();
							jQuery("#loading-icon").hide();
							
							jQuery("#export_popup1").html('Feed Exported Successfully.');
							jQuery("#export_popup1").html(response.download);
							
							
						}else if(response.export_can_proceed==false){
							jQuery("#loading-icon").hide();
							jQuery(".nav-bar").hide();
							jQuery("#export_popup1").hide();
							jQuery("#error_message").html(response.error_message);
							jQuery("#error_message").show();
						}
					}
				});
		
			},
			getPercent:function(countOfUpdated,totalRecords) {
				return Math.ceil((countOfUpdated/totalRecords)*1000)/10;
			},
			fillProcessbar:function(width){
				
				var elem = jQuery("#myBar");
				/*
				if (width >= 100) {
					alert("width::"+width);
					clearInterval(id);
				} else {*/
					console.log('width::'+width);
					jQuery("#myBar").css({'width': width+'%'});
				//elem.style.width = width + '%';
				//document.getElementById("label").innerHTML = width * 1  + '%';
				jQuery("#label").innerHTML =width * 1  + '%';
				//}
			}
		
});
 	return jQuery.Productfeed.exportfeed;
});
