var config = {
    shim: {
   'mapping': {
            deps: ['jquery','jquery/ui']
    },
	'conditions': {
            deps: ['jquery','jquery/ui']
    },
	'categorylist': {
            deps: ['jquery','jquery/ui']
    },
	'csvcontent': {
            deps: ['jquery','jquery/ui']
    },
	'codemirror':{
			deps: ['jquery','jquery/ui']
	},
	'customxmledit':{
			deps: ['jquery','codemirror','simple','prototype']
	},
	'exportfeed' : {
		deps: ['jquery','mage/template','jquery/ui','mage/translate','Magento_Ui/js/modal/modal']
	}
		 
		
  },
     map: {
       '*': {
            'mapping' : 'Magebees_Productfeed/js/mapping',
            'conditions' : 'Magebees_Productfeed/js/conditions',
            'categorylist' : 'Magebees_Productfeed/js/categorylist',
            'csvcontent' : 'Magebees_Productfeed/js/csvcontent',
            'customprototype' : 'Magebees_Productfeed/js/customprototype',
            'codemirror': 'Magebees_Productfeed/js/codemirror',
            'simple': 'Magebees_Productfeed/js/simple',
            'customxmledit': 'Magebees_Productfeed/js/customxmledit',
            'exportfeed': 'Magebees_Productfeed/js/exportfeed'
       }
    },
    paths: {
    },
};

