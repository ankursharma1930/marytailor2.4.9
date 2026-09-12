<?php
namespace Magebees\Productfeed\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{	
	 protected $ftp;
	protected $sftp;
    public function __construct(
		\Magento\Framework\Filesystem\Io\Ftp $ftp,
		\Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\App\Helper\Context $context
    ) {
		$this->ftp = $ftp;
		$this->sftp = $sftp;
	    parent::__construct($context);
    	
	}
	
	
	
	public function ftp_file_upload($feedname, $existingfilepath , $ftp_settings_info)
	{
		$uploaded = false;
		$use_active_mode = $ftp_settings_info['use_active_mode'];
		$host = $ftp_settings_info['host'];
		$username = $ftp_settings_info['username'];
		$password = $ftp_settings_info['password'];
		$directory_path = $ftp_settings_info['directory_path'];	
		if($use_active_mode==1)
		{
		$passive = false;
		}else {
		$passive = true;
		}
		
		
		
		
		$open = $this->ftp->open(
               array(
                   'host' => $host,
                   'user' => $username,
                   'password' => $password,
				   'passive' => $passive,
				   'path' => $directory_path,
				   'file_mode' => FTP_BINARY
               )
           );
		if ($open) {
			$uploaded = true;
			  $this->ftp->write($feedname, $existingfilepath);
			  $this->ftp->close();
           }
			return $uploaded;
	}
	public function sftp_file_upload($feedname, $existingfilepath , $sftp_settings_info)
	{
		$uploaded = false;
		$use_active_mode = $sftp_settings_info['use_active_mode'];
		$host = $sftp_settings_info['host'];
		$username = $sftp_settings_info['username'];
		$password = $sftp_settings_info['password'];
		$directory_path = $sftp_settings_info['directory_path'];	
		if($use_active_mode==1)
		{
		$passive = false;
		}else {
		$passive = true;
		}
		$open = $this->sftp->open(
               array(
                   'host' => $host,
                   'username' => $username,
                   'password' => $password,
				   'timeout' => 90
				)
           );
				
		$directory_list = array_filter(explode("/",$directory_path));
			foreach($directory_list as $dir):
				$this->sftp->cd($dir);
			endforeach;
		$current_dir_path = $this->sftp->pwd();
		
		if (strpos($current_dir_path, $directory_path) !== false) {
  			$uploaded = $this->sftp->write($feedname, $existingfilepath);	
			$this->sftp->close();
		}
		return $uploaded;
		
			
		
		
	}

}


