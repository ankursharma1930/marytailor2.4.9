define([
    'codemirror',
    'simple',
    'prototype'
], function(CodeMirror) {

        CodeMirror.defineSimpleMode("mgfeed", {
            start: [
                {regex: /"(?:[^\\]|\\.)*?"/, token: "mgom"},
                {
                    regex: /(?:attribute|format|optional|parent|cdata|modify)\b/,
                    token: "string"
                },
                {regex: /attribute|custom_field|text|images/, token: "mgom"},
                {regex: /\<!\[CDATA\[/, token: "mgcdata", next: "mgcdata"},
                {regex: /\</, token: "mgtag", next: "mgtag"},

                {regex: /[\{|%]/, token: "def"},
                {regex: /[\}|%]/, token: "def"},

            ],
            mgtag: [
                {regex: /.*?>/, token: "mgtag", next: "start"},
                {regex: /.*/, token: "mgtag"}
            ],
            mgcdata: [
                {regex: /.*?]]>/, token: "mgcdata", next: "start"},
                {regex: /.*/, token: "mgcdata"}
            ]
        });


        var xmlEditor = {
            editor: null,
            header: null,
            footer: null,
            selectedRow: {},
            updateMode: false,
            navigator: {},
            modifyTemplate: null,
            modifyConfig: null,
            modifyArgs: null,
            modifyCount: 0,
            buttons: {
                insert: null,
                update: null
            },
            updateBtn: null,
            
			clearSelectedRow: function(){
                this.updateMode = false;
                
				this.selectedRow = {
					tag: null,
					type: null,
					value: null,
					format: null,
					length: null,
					optional: null,
					parent: null,
					cdata:null
				}

                var modifyContainer = $('feed_xml_content_modify_container');
                if (modifyContainer){
                    modifyContainer.innerHTML = '';
                }
            },
            refresh: function(){
                this.editor.refresh();
                this.editor.save();

                this.header.refresh();
                this.header.save();
				this.feedmaintag.refresh();
                this.feedmaintag.save();
                this.footer.refresh();
                this.footer.save();
            },
            init: function(modifyTemplate, modifyConfig, modifyArgs){
				
				
				jQuery('#feed_xml_content_container').on('click', '.value-setting', function() {
					
					var current_tr = jQuery(this).parent().parent();
					var next_tr = jQuery(current_tr).next();
					var next_next_tr = jQuery(current_tr).next().next();
					if (next_tr.is(':visible') && next_next_tr.is(':visible'))
					{
						console.log('is visible');
						
						jQuery(next_tr).hide();
						jQuery(next_next_tr).hide();
						
						return;
					}
					if (next_tr.is(':hidden') && next_next_tr.is(':hidden'))
					if (next_tr.is(':hidden'))
					{
						console.log('is hidden');
						jQuery(next_tr).show();
						jQuery(next_next_tr).show();
						
					}
					
					
					
					
				});
				
				
                this.modifyTemplate = modifyTemplate;
                this.modifyConfig = modifyConfig;
                this.modifyArgs = modifyArgs;

                this.editor = CodeMirror.fromTextArea($('xml_body'), {
                    mode: 'mgfeed',
                    alignCDATA: true,
                    lineNumbers    : true,
                    viewportMargin : Infinity
                });
		
                this.header = CodeMirror.fromTextArea($('xml_header'), {
                    mode: 'mgfeed',
                    alignCDATA: true,
                    lineNumbers: true,
                    viewportMargin : Infinity
                });
				this.feedmaintag = CodeMirror.fromTextArea($('feed_xml_item'), {
                    mode: 'mgfeed',
                    alignCDATA: true,
                    viewportMargin : Infinity
                });
                this.footer = CodeMirror.fromTextArea($('xml_footer'), {
                    mode: 'mgfeed',
                    alignCDATA: true,
                    lineNumbers: true,
                    viewportMargin : Infinity
                });

                
				this.editor.setSize('100%', 400);
                this.header.setSize('100%', 100);
				this.feedmaintag.setSize('100%', 100);
                this.footer.setSize('100%', 100);

                this.editor.on("cursorActivity", this.cursorActivity.bind(this));

                this.clearSelectedRow();
                this.initNavigator();
                this.initButtons();
                this.updateNavigator();

                setInterval(this.refresh.bind(this), 100);
            },
            initNavigator: function (){
                var container = $('xml_table');
               
				this.navigator = {
					xml_tag: container.down("#xml_field_title"),
					insert_type: container.down("#insert_type"),
            
				insert_attr_attribute: container.down("#insert_attr_attribute"),
				insert_attr_custom_field: container.down("#insert_attr_custom_field"),
				insert_attr_category: container.down("#insert_attr_category"),
				insert_attr_text: container.down("#insert_attr_text"),
				insert_attr_images: container.down("#insert_attr_images"),
				
				insert_format: container.down("#insert_format"),
			
				
				insert_length: container.down("#insert_length"),
			
				use_parent: container.down("#use_parent"),
				use_cdata: container.down("#use_cdata")
				
			}
            },
            initButtons: function(){
               var container = $('xml_table');

               this.buttons.insert = container.down("#insert_button");
               this.buttons.update = container.down("#update_button");

               this.buttons.insert.observe('click', this.inserRow.bind(this));
               this.buttons.update.observe('click', this.updateRow.bind(this));
            },
            getXMLRowFormat: function(){
                var ret = "";
                switch(this.navigator.insert_type.value){
                    case "images":
                        //ret = this.navigator.insert_image_format.value;
                        break;
                    default:
                        ret = this.navigator.insert_format.value;
                        break;
                }

                return ret;
            },
            getRow: function(){
				
				
                var modifyArr = [];
                for(var idx = 0; idx < this.modifyCount; idx++)
                {
                    var modify = $('field_row_' + idx + '_modify');

                    if (modify){
                        var modifyValue = modify.value;
                        var args = [];
                        if (this.modifyArgs[modifyValue]){
                            args = this.modifyArgs[modifyValue];
                        }

                        var modifyString = modify.value;

                        if (args.length > 0 ){
                            modifyString += ':'
                            var values = [];
                            for(var argIdx = 0; argIdx < args.length; argIdx++){
                               var arg = $('field_row_' + idx + '_arg' + argIdx);
                               if (arg){
                                   values.push(arg.value);
                               }
                            }
                            modifyString += values.join("^");
                        }
                        modifyArr.push(modifyString);
                    }
                }

              
				var insertAttr = "insert_attr_" + this.navigator.insert_type.value;
				var repl = {
					':xml_tag': this.navigator.xml_tag.value,
					':insert_type': this.navigator.insert_type.value,
					':value': this.navigator[insertAttr].value,
					':insert_format': this.getXMLRowFormat(),
					':insert_length': this.navigator.insert_length.value,
					/*':insert_optional': this.navigator.insert_optional.value,*/
					':parent': this.navigator.use_parent.value,
					':cdata':this.navigator.use_cdata.value
					
				};
				
				console.log(this.navigator.use_cdata.value);
            	/* Set CDATA template in the string..*/
				if(this.navigator.use_cdata.value=='yes'){
				var tpl = '<![CDATA[{type=":insert_type" value=":value" format=":insert_format" length=":insert_length"  parent=":parent" cdata=":cdata"}]]>';	
				}else{
					var tpl = '{type=":insert_type" value=":value" format=":insert_format" length=":insert_length"  parent=":parent" cdata=":cdata"}';	
				}
				$H(repl).each(function(item){
                    tpl = tpl.replace(eval('/' + item.key + '/g'), item.value);
                });

                if (this.navigator.xml_tag.value){
                    tpl = "<" + this.navigator.xml_tag.value + ">" + tpl + "</" + this.navigator.xml_tag.value + ">";
                }

                return tpl;
            },
            updateRow: function(){

                var originLine = this.editor.getLine(this.editor.getCursor().line);

                var line = this.getRow();

                this.editor.replaceRange(line, {
                    line: this.editor.getCursor().line,
                    ch: 0
                }, {
                    line: this.editor.getCursor().line,
                    ch: originLine.length
                });

				console.log('updateRow Start 1');
				jQuery('#feed_xml_content_container').find('#xml_field_title').val('');
				
				jQuery("#xml_field_title").val('');
				
				jQuery('#feed_xml_content_container').find("#insert_attr_text").val('');
				
				jQuery('#feed_xml_content_container').find("#insert_length").val('');
				
				jQuery('#feed_xml_content_container').find("#insert_type").val(jQuery("#insert_type option:first").val());
				
				jQuery('#feed_xml_content_container').find("#insert_attr_attribute").val(jQuery("#insert_attr_attribute option:first").val());
				
				jQuery('#feed_xml_content_container').find("#insert_attr_category").val(jQuery("#insert_attr_category option:first").val());
				
				jQuery('#feed_xml_content_container').find("#insert_attr_images").val(jQuery("#insert_attr_images option:first").val());
				
				jQuery('#feed_xml_content_container').find("#insert_format").val(jQuery("#insert_format option:first").val());
				
				jQuery('#feed_xml_content_container').find("#use_parent").val(jQuery("#use_parent option:first").val());
				
				jQuery('#feed_xml_content_container').find("#use_cdata").val(jQuery("#use_cdata option:first").val());
				
				jQuery('#feed_xml_content_container').find("#insert_attr_text").hide();
				
				jQuery('#feed_xml_content_container').find("#insert_attr_category").hide();
				

				jQuery('#feed_xml_content_container').find("#insert_attr_images").hide();
				
				jQuery('#feed_xml_content_container').find("#insert_attr_attribute").show();
				
				
				
				
				
            },
            inserRow: function(){
                
				this.editor.replaceSelection(this.getRow() + '\n');


				jQuery('#feed_xml_content_container').find('#xml_field_title').val('');
				jQuery('#feed_xml_content_container').find("#insert_attr_text").val('');
				jQuery('#feed_xml_content_container').find("#insert_length").val('');
				jQuery('#feed_xml_content_container').find("#insert_type").val(jQuery("#insert_type option:first").val());
				jQuery('#feed_xml_content_container').find("#insert_attr_attribute").val(jQuery("#insert_attr_attribute option:first").val());

				jQuery('#feed_xml_content_container').find("#insert_attr_category").val(jQuery("#insert_attr_category option:first").val());
				jQuery('#feed_xml_content_container').find("#insert_attr_images").val(jQuery("#insert_attr_images option:first").val());
				jQuery('#feed_xml_content_container').find("#insert_format").val(jQuery("#insert_format option:first").val());
				jQuery('#feed_xml_content_container').find("#use_parent").val(jQuery("#use_parent option:first").val());
				jQuery('#feed_xml_content_container').find("#use_cdata").val(jQuery("#use_cdata option:first").val());
				jQuery('#feed_xml_content_container').find("#insert_attr_text").hide();
				jQuery('#feed_xml_content_container').find("#insert_attr_category").hide();
				jQuery('#feed_xml_content_container').find("#insert_attr_images").hide();
				jQuery('#feed_xml_content_container').find("#insert_attr_attribute").show();

				
            },
           
			cursorActivity: function(){
        this.clearSelectedRow();
        
        var line = this.editor.getLine(this.editor.getCursor().line);
        
        var tagMatch = line.match(/<([^>]+)>(.*?)<\/\1>/);
        if (tagMatch && tagMatch.length == 3){
			this.selectedRow.tag = tagMatch[1];
			this.updateMode = true;
        }
        
        var varsRe = /(type|value|format|length|optional|parent|cdata)="(.*?)"/g
        var varsArr;

        while ((varsArr = varsRe.exec(line)) != null) {
            if (varsArr && varsArr.length == 3){
                if (this.selectedRow[varsArr[1]] !== undefined){
                    this.selectedRow[varsArr[1]] = varsArr[2];
                }
				if (varsArr[1] == "type"){

					var dropdown = jQuery("#insert_type");
					console.log(this.selectedRow);
					console.log("varsArr:1:"+varsArr[1]);
					console.log("varsArr:2:"+varsArr[2]);
					if(varsArr[2]=='attribute'){
						
						jQuery('#feed_xml_content_container').find('#insert_attr_attribute').show();
						jQuery('#feed_xml_content_container').find('#insert_attr_category').hide();
						jQuery('#feed_xml_content_container').find('#insert_attr_text').hide();
						jQuery('#feed_xml_content_container').find('#insert_attr_images').hide();
						
						
						
					}else if(varsArr[2]=='category'){
						
						
						jQuery('#feed_xml_content_container').find('#insert_attr_attribute').hide();
						jQuery('#feed_xml_content_container').find('#insert_attr_category').show();
						jQuery('#feed_xml_content_container').find('#insert_attr_text').hide();
						jQuery('#feed_xml_content_container').find('#insert_attr_images').hide();
						
						
						
						
					}else if(varsArr[2]=='text'){
						
						jQuery('#feed_xml_content_container').find('#insert_attr_attribute').hide();
						jQuery('#feed_xml_content_container').find('#insert_attr_category').hide();
						jQuery('#feed_xml_content_container').find('#insert_attr_text').show();
						jQuery('#feed_xml_content_container').find('#insert_attr_images').hide();
					}
				}
				
                this.updateMode = true;
            }
        }
         this.restoreModify(line);
        this.updateNavigator();
		    console.log("this.updateMode"+this.updateMode);    
    },
            restoreModify: function(line){

                var varsRe = /(modify)="(.*?)"/g
                var varsArr = varsRe.exec(line);


                if (varsArr && varsArr.length == 3){
                    var modificators = varsArr[2].split("|");
                    for(var idx in modificators){
                        var modificator = modificators[idx];
                        if (typeof(modificator) != 'function'){
                            var modificatorArr = modificator.split(/:(.+)?/, 2);

                            var modify = modificatorArr[0];

                            if ($(this.modifyConfig).indexOf(modify) != -1){
                                var rowIndex = this.modifyItem();
                                var select = $('field_row_' + rowIndex + '_modify');
                                if (select){
                                    select.value = modify;
                                    this.changeModifier(select);
                                }

                                var args = []

                                if (this.modifyArgs[select.getValue()]){
                                    args = this.modifyArgs[select.getValue()];
                                }

                                if (args.length > 0 && modificatorArr[1]){
                                    var values = modificatorArr[1].split("^");

                                    for(var idx = 0; idx < args.length; idx++) {
                                        var id = select.id.replace("_modify", "_arg" + idx)
                                        var input = $(id);
                                        if (input && values[idx]) {
                                            input.value = values[idx];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
           
			updateNavigator: function(){
        this.setValue(this.navigator.xml_tag, this.selectedRow.tag);
        this.setValue(this.navigator.insert_type, this.selectedRow.type);
        this.setValue(this.navigator.insert_length, this.selectedRow.length);
        //this.setValue(this.navigator.insert_optional, this.selectedRow.optional);
        this.setValue(this.navigator.use_parent, this.selectedRow.parent);
		this.setValue(this.navigator.use_cdata, this.selectedRow.cdata);
        
        switch(this.selectedRow.type){
            case "attribute":
                this.setValue(this.navigator.insert_attr_attribute, this.selectedRow.value);
                this.setValue(this.navigator.insert_format, this.selectedRow.format);
            break;
            case "custom_field":
                this.setValue(this.navigator.insert_attr_custom_field, this.selectedRow.value);
                this.setValue(this.navigator.insert_format, this.selectedRow.format);
            break;
            case "category":
                this.setValue(this.navigator.insert_attr_category, this.selectedRow.value);
                this.setValue(this.navigator.insert_format, this.selectedRow.format);
            break;
            case "text":
                this.setValue(this.navigator.insert_attr_text, this.selectedRow.value);
                this.setValue(this.navigator.insert_format, this.selectedRow.format);
            break;
            case "images":
                this.setValue(this.navigator.insert_attr_images, this.selectedRow.value);
                //this.setValue(this.navigator.insert_image_format, this.selectedRow.format);
            break;
        }
        
        this.disableCol('xml_tag_col', this.selectedRow.tag);
        this.disableCol('xml_insert_type', this.selectedRow.value);
        this.disableCol('attribute_values', this.selectedRow.value);
        this.disableCol('xml_image_format', this.selectedRow.format);
        this.disableCol('xml_insert_length', this.selectedRow.length);
        //this.disableCol('xml_insert_optional', this.selectedRow.optional);
        this.disableCol('xml_use_parent', this.selectedRow.parent);
		//this.disableCol('xml_use_cdata', this.selectedRow.cdata);        
		if (this.updateMode){
                    this.buttons.update.removeClassName('hidden');
                    this.buttons.insert.addClassName('hidden');;
                } else {
                    this.buttons.update.addClassName('hidden');
                    this.buttons.insert.removeClassName('hidden');
                }
    },
	 disableCol: function(colId, value){
        var container = $('xml_control_container');
        
        if (value === null && this.updateMode){
            container.select('#' + colId + ' input, #' + colId + " select").each(function(el){
                el.disabled = true;
            });
        } else {
            container.select('#' + colId + ' input, #' + colId + " select").each(function(el){
                el.disabled = false;
            });
        }  
    },
            setValue: function(input, value){
                if (value !== null) {
                    input.setValue(value)
                }
            },
            modifyItem: function(a){
                var container = $('feed_xml_content_modify_container');
                var data = {
                    index: this.modifyCount++
                };
                if (container){
                    Element.insert(container, {
                        bottom : this.modifyTemplate({
                            data: data
                        })
                    });
                }
                return data.index;
            },
            changeModifier: function(select){
                var td = select.up('td');

                var args = []

                if (this.modifyArgs[select.getValue()]){
                    args = this.modifyArgs[select.getValue()];
                }

                td.select('input').each(function(input){
                    input.hide();
                });

                for(var idx = 0; idx < args.length; idx++){
                    var id = select.id.replace("_modify", "_arg" + idx)
                    var input = td.down("#" + id);
                    if (input){
                        input.show();
                        input.setAttribute('placeholder', args[idx]);
                    }
                }
            },
			change_columntype:function(row){
				
				$(row).up('tr').down('td#attribute_values').select('select').each(function(select){
					select.hide();
				}); 
    
				$(row).up('tr').down('td#attribute_values').select('input').each(function(select){
					select.hide();
				});
				
				$(row).up('tr').down('#insert_attr_' + $(row).value).show();
				
				var tr = $(row).up('tr').next();
				var tr_next = $(row).up('tr').next().next();
				jQuery("#insert_attr_text").val('');
				switch(row.value){
					case "images":
						tr_next.down('#insert_format').hide();
						break;
					default:
						
						tr_next.down('#insert_format').show();
						break;
				}
			},
            deleteItem: function(event) {
                var tr = Event.findElement(event, 'tr');
                if (tr) {
                    Element.select(tr, ['input', 'select']).each(function(element) {
                        element.remove();
                    });
                    Element.remove(tr);
//                    Element.addClassName(tr, 'no-display template');
                }
                return false;
            }
        }

        return xmlEditor

    }
)

