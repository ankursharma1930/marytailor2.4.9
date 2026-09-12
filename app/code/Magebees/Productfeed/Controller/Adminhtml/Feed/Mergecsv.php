<?php
namespace Magebees\Productfeed\Controller\Adminhtml\Feed;
use DOMDocument;
use DOMXPath;
class Mergecsv extends \Magento\Backend\App\Action
{
	protected $_coreRegistry = null;
	protected $resultPageFactory;
	protected $_dir = null;
	protected $csv_content = null;
	protected $xml_content = null;
	protected $csv_header = null;
	protected $_proceed_next=true;
	protected $xmlcontent = null;
	protected $_feed = null;
	protected $feedname = null;
	protected $filesystem;
	protected $_scopeConfig;	protected $_filesystem;	protected $_serializer;
	
	
	//protected $_product_data_collection=array();
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
		\Magebees\Productfeed\Model\Feed $_feed,
		 \Magento\Framework\Filesystem\DirectoryList $dir,
		 \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig,
		 \Magento\Framework\Filesystem $filesystem,
		\Magento\Framework\Serialize\SerializerInterface $serializer
	) {
        $this->resultPageFactory = $resultPageFactory;
		 $this->_scopeConfig = $_scopeConfig;
        $this->_coreRegistry = $registry;
		$this->_dir = $dir;
		$this->_feed = $_feed;
		$this->_filesystem = $filesystem;
		$this->_serializer = $serializer;
	    parent::__construct($context);
    }
 
  		
	public function execute()
	{
		
		/* Merge Tempery file into main file.*/
		try {
			$data = array();
			$filesystem = $this->_objectManager->get('Magento\Framework\Filesystem');
			$reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
			
			$scope_info = $this->_scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
			
			$path = $reader->getAbsolutePath($file_path);
			$timestamp = $this->getRequest()->getParam('timestamp');
			$filename = $this->getRequest()->getParam('filename');
			$this->feedname = $this->getRequest()->getParam('feedname');
			$page_ctn = $this->getRequest()->getParam('page');
			$currentPage = $this->getRequest()->getParam('processPage');
			$feed_id = $this->getRequest()->getParam('feed_id');
			$download_url = "";
			if ($feed_id){
            $this->_feed->load($feed_id);
			
			if($this->_feed->getType()=="csv"){
				$this->csv_content = json_decode($this->_feed->getContent(),true);
				$this->csv_header = $this->csv_content['csv_header'];
				$combiner_status = $this->csvMerge($path,$timestamp,$filename,$page_ctn,$currentPage);
			}else if($this->_feed->getType()=="xml"){
				$xml_contentfilename = $this->_feed->getName()."-content.xml";
				$this->xml_content = json_decode($this->_feed->getContent(),true);
				$combiner_status = $this->xmlMerge($path,$timestamp,$filename,$page_ctn,$currentPage,$xml_contentfilename);
			}
			$this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($combiner_status));
			return;
			}
			
		} catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
				$data['error_message'] = $e->getMessage();
				$this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($data));
                return;
            } catch (\Exception $e) {
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
				$data['error_message'] = $e->getMessage();
				$this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($data));
				return;
			}
		
		
		
	}
	public function csvMerge($path,$timestamp,$filename,$page_ctn,$currentPage){
		$download_url = "";
		$cws_csv_feed_header=$path.'cws_csv_feed_header-'.$timestamp.'.obj';
		$header_string_obj=file_get_contents($cws_csv_feed_header);
		$header_template=$this->_serializer->unserialize($header_string_obj);
		$tempFile = fopen($path.$filename."-tmp-".$currentPage,"r");
		$tmp_data=array();
		$temp_header=array();
		$_temp_first=true;
		while(!feof($tempFile))
		{
		  if($_temp_first)
		  {
			$temp_header=fgetcsv($tempFile);
			$_temp_first=false;
		  }else{
			$temp_single_record=array();
			$temp_data = fgetcsv($tempFile);
			
			if (is_array($temp_data) || is_object($temp_data)){
				//foreach(fgetcsv($tempFile) as $key=>$value){
				foreach($temp_data as $key=>$value){
					$temp_single_record[$temp_header[$key]]=$value;	
					//$temp_single_record[$value]=$value;	
				}
			}
			
			if (array_filter($temp_single_record)) {
				$tmp_data[]=$temp_single_record;
			}
		  }
		}
		fclose($tempFile);
		unlink($path.$filename."-tmp-".$currentPage);
	
		if($this->csv_content['csv_delimiter']=="comma"){
			$field_separated = ",";
		}else if($this->csv_content['csv_delimiter']=="semicolon"){
			$field_separated = ";";
		}
		else if($this->csv_content['csv_delimiter']=="pipe"){
			$field_separated = "|";
		}else if($this->csv_content['csv_delimiter']=="tab"){
			$field_separated = "\t";
		}
		if($this->csv_content['csv_enclosure']=="double_quote"){
			$field_enclosed = '"';
		}else if($this->csv_content['csv_enclosure']=="single_quote"){
			$field_enclosed = "'";
		}
		else if($this->csv_content['csv_enclosure']=="space"){
			$field_enclosed = " ";
		}else if($this->csv_content['csv_enclosure']=="none"){
			$field_enclosed = '"';
		}
		
		if($currentPage=='1'){
			$fp = fopen($path.$this->feedname, 'w');
			/* Add Header If  Column Names From Options */
			if($this->csv_content['csv_header']=="1"){
				fputcsv($fp, array_values($header_template),$field_separated,$field_enclosed);
				
				
			}
			//fputcsv($fp, array_values($header_template));
			
		}else{
			$fp = fopen($path.$this->feedname, 'a');
		}
		
		foreach ($tmp_data as $product) {
			$o_data=array_fill_keys(array_values($header_template), '');
			foreach($product as $o_key=>$o_val)
			{
				if (in_array($o_key, $header_template)) {
					$o_data[$o_key]=$o_val;
				}
			}
			//fputcsv($fp, array_values($o_data));
			fputcsv($fp, array_values($o_data),$field_separated,$field_enclosed);
			//fputcsv(file,fields,$field_separated,$field_enclosed) 
		}					
		fclose($fp);
		$_file = $this->_objectManager->create('Magento\Framework\Filesystem\Driver\File');;
		$dir_permission = 0755;
		$file_permission = 0644;
		if(!$_file->isExists($path))
		{
					$_file->createDirectory($path,$dir_permission);
		}
		$_file->changePermissionsRecursively($path,$dir_permission,$file_permission);
			
		if($currentPage==$page_ctn){
			$this->_proceed_next=false;
			unlink($cws_csv_feed_header);
			
			$storeId = 0; // add your store id
$baseUrl = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')
                     ->getStore($storeId)
                     ->getBaseUrl();
$scope_info = $this->_scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = "var/export/";
			}
$download_path = $baseUrl.$file_path.$this->feedname;
			if(file_exists($path.$this->feedname)){
			$download_path = $this->getUrl('*/*/downloadexportedfile',array('file'=>$this->feedname));	
			}
			
			
		$download_url = "";
		$download_url = "<div class='message message-success success'><div data-ui-id='messages-message-success'>Generated csv File : <b style='font-size:18px'><a href='".$download_path."' target='_blank'>".$this->feedname."</a></b></div></div>";
			
			/* Code added For Set Feed Status Flag */
			$download_url_info = array();
				if($this->_feed->getId()){
					$download_url_info = $this->_feed->getDownloadUrl();
					if($this->isJSON($download_url_info)){
						if(isset($download_url_info['feedname'])){
						$this->feedname = $download_url_info['feedname'];	
						}
						$this->feedname=preg_replace('/\s+/', '', $this->feedname);
						$download_url_info = json_decode($download_url_info, true);
						$download_url_info['feedstatus'] = "1";
						$download_url_info['feedname'] = $this->feedname;
						
						$this->_feed->setDownloadUrl(json_encode($download_url_info));
						$this->_feed->save();
						
						}
					
					/* Code Start For Upload Feed File On other server Using FTP */	
			$ftp_settings = $this->_feed->getFtpSettings();
				if($this->isJSON($ftp_settings)){
				$ftp_settings_info = json_decode($ftp_settings, true);
					if($ftp_settings_info['enable_ftp_upload'])
					{
					$ori_file_path = $path.$this->feedname;
					$feedname = $this->feedname;
					$helper = $this->_objectManager->get('\Magebees\Productfeed\Helper\Data'); 
					if($ftp_settings_info['protocol']=='sftp')
					{
					$uploaded = $helper->sftp_file_upload($feedname,$ori_file_path,$ftp_settings_info);
					}else if($ftp_settings_info['protocol']=='ftp')
					{
					$uploaded = $helper->ftp_file_upload($feedname,$ori_file_path,$ftp_settings_info);
					}
					
						if(!$uploaded)
						{
							$this->messageManager->addError(__('Enable To Upload File On Server.'));
						}else{
						$this->messageManager->addSuccess(__('Generated Feed File Uploaded Successfully.'));
						}
					}
				}
			/* Code End For Upload Feed File On other server Using FTP */	
					
					} 
		}
		
		$currentPage++;
		$csv_combiner_status = array('proceed_next'=>$this->_proceed_next,'timestamp'=>$timestamp,'filename'=>$filename,'feedname'=>$this->feedname,'page'=>$page_ctn,'processPage'=>$currentPage,'download'=>$download_url);
		return $csv_combiner_status;
	}
	public function xmlMerge($path,$timestamp,$filename,$page_ctn,$currentPage,$xml_contentfilename){
		
		
		$download_url = "";
		if(file_exists($path.$timestamp."-rows.xml")){
			unlink($path.$timestamp."-rows.xml");
		}
		if(file_exists($path.$xml_contentfilename)){
			unlink($path.$xml_contentfilename);
		}
		if($this->feedname!=""){
				if(file_exists($path.$this->feedname))
		{
			$mainFile = fopen($path.$this->feedname,"a");
		}else{
			$mainFile = fopen($path.$this->feedname,"w");
		}
		fclose($mainFile);
		}
		
		
			//fwrite($tempFile,$xml_content);
		
		$tempFile = fopen($path.$filename."-tmp-".$currentPage,"r");
		$file_content = file_get_contents($path.$filename."-tmp-".$currentPage);
		fclose($tempFile);
		unlink($path.$filename."-tmp-".$currentPage);

		if($currentPage=='1'){
			$fp = fopen($path.$this->feedname, 'w');
			
			
		}else{
			$fp = fopen($path.$this->feedname, 'a');
		}
		fwrite($fp, $file_content);
		fclose($fp);
		if($currentPage==$page_ctn){
			$this->_proceed_next=false;
			
			$storeId = 0; // add your store id
			$baseUrl = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')
                     ->getStore($storeId)
                     ->getBaseUrl();
			$scope_info = $this->_scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = "var/export/";
			}
			$download_path = $baseUrl.$file_path.$this->feedname;
			if(file_exists($path.$this->feedname)){
			$download_path = $this->getUrl('*/*/downloadexportedfile',array('file'=>$this->feedname));	
			}
			
			
			$download_url = "";

			$download_url = "<div class='message message-success success'><div data-ui-id='messages-message-success'>Generated xml File : <b style='font-size:18px'><a href='".$download_path."' target='_blank'>".$this->feedname."</a></b></div></div>";
			/* Code added For Remove Blank tag from xml */
			$xml_final_file_content = file_get_contents($path.$this->feedname);
			$xml_file_content = str_replace("<![CDATA[]]>","",$xml_final_file_content);
			$this->xmlcontent = new DOMDocument;
			$this->xmlcontent->preserveWhiteSpace = false;
			$this->xmlcontent->loadxml($xml_file_content);
			$xpath = new DOMXPath($this->xmlcontent);
			
			while (($notNodes = $xpath->query('//*[not(node())]')) && ($notNodes->length)) {
				foreach($notNodes as $node) {
				$node->parentNode->removeChild($node);
				}
			}
			$this->xmlcontent->formatOutput = true;
			$fp = fopen($path.$this->feedname, 'w');
			fwrite($fp, $this->xmlcontent->savexml());
			fclose($fp);
			$_file = $this->_objectManager->create('Magento\Framework\Filesystem\Driver\File');;
					$dir_permission = 0755;
					$file_permission = 0644;
					if(!$_file->isExists($path))
					{
					$_file->createDirectory($path,$dir_permission);
					}
					$_file->changePermissionsRecursively($path,$dir_permission,$file_permission);
			/* Code added end For Remove Blank tag from xml */
			/* Code added For Set Feed Status Flag */
			$download_url_info = array();
				if($this->_feed->getId()){
					$download_url_info = $this->_feed->getDownloadUrl();
					if($this->isJSON($download_url_info)){
						if(isset($download_url_info['feedname'])){
						$this->feedname = $download_url_info['feedname'];	
						}
						$download_url_info = json_decode($download_url_info, true);
						$download_url_info['feedstatus'] = "1";
						$this->feedname=preg_replace('/\s+/', '', $this->feedname);
						$download_url_info['feedname'] = $this->feedname;
						$this->_feed->setDownloadUrl(json_encode($download_url_info));
						$this->_feed->save();
						
						}
					}
			
			/* Code completed For Set Feed Status Flag */		
			/* Code Start For Upload Feed File On other server Using FTP */	
			$ftp_settings = $this->_feed->getFtpSettings();
				if($this->isJSON($ftp_settings)){
				$ftp_settings_info = json_decode($ftp_settings, true);
					if($ftp_settings_info['enable_ftp_upload'])
					{
					$ori_file_path = $path.$this->feedname;
					$feedname = $this->feedname;
					$helper = $this->_objectManager->get('\Magebees\Productfeed\Helper\Data'); 
					if($ftp_settings_info['protocol']=='sftp')
					{
					$uploaded = $helper->sftp_file_upload($feedname,$ori_file_path,$ftp_settings_info);
					}else if($ftp_settings_info['protocol']=='ftp')
					{
					$uploaded = $helper->ftp_file_upload($feedname,$ori_file_path,$ftp_settings_info);
					}
					
					if(!$uploaded)
						{
							$this->messageManager->addError(__('Enable To Upload File On Server.'));
						}else{
						$this->messageManager->addSuccess(__('Generated Feed File Uploaded Successfully.'));
						}
					}
				}
			/* Code End For Upload Feed File On other server Using FTP */	
				
				
			
		}
		$currentPage++;
		$csv_combiner_status = array('proceed_next'=>$this->_proceed_next,'timestamp'=>$timestamp,'filename'=>$filename,'feedname'=>$this->feedname,'page'=>$page_ctn,'processPage'=>$currentPage,'download'=>$download_url);
		return $csv_combiner_status;
	}
	public function isJSON($string){
			return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
		}
	
	protected function _isAllowed(){
       return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
	}
}
