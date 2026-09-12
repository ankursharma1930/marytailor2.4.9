<?php
namespace Magebees\Productfeed\Controller\Adminhtml\Feed;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Testconnection extends \Magento\Backend\App\Action
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;
    protected $scopeConfig;
    protected $_ObjectManager;
    
	/**
     * @var \Magento\Framework\Filesystem\Io\Ftp
     */
    private $ftp;

    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    private $sftp;

    /**
     * @var string
     */
	
	const REMOTE_TIMEOUT = 10;
    const FTP_PORT = 21;
	const SSH2_PORT = 22;
	
    
	public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Filesystem\Io\Ftp $ftp,
        \Magento\Framework\Filesystem\Io\Sftp $sftp
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_coreRegistry = $registry;
		 $this->ftp = $ftp;
        $this->sftp = $sftp;
		$this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }
 
        
    public function execute()
    {
		if ($this->getRequest()->getPostValue()) {
			$data = $this->getRequest()->getPostValue();
			
			try {
				$result = array();
				
				if(($data['protocol']=='sftp') && (isset($data['host']) && isset($data['username']) && isset($data['password'])))
				{
					$host = $data['host'];
					$user = $data['username'];
					$pass = $data['password'];
					$directory_path = $data['directory_path'];
					if (false !== strpos($host, ':')) {
					list($Host, $Port) = explode(':', $host);
					} else {
						$Host = $host;
						$Port = self::SSH2_PORT;
					}
					try {
						$_connection = new \phpseclib\Net\SFTP($Host, $Port);
						} catch (\Exception $e) {
						$_connection = new \phpseclib3\Net\SFTP($Host, $Port);
						}
						
					if (!$_connection->login($user, $pass)) {
						throw new \Exception(
							sprintf("Unable to open SFTP connection as %s@%s", $user, $Host));
					$result['connection_status'] = false;
					$result['message'] = "Unable to open SFTP connection as ".$user." ".$Host; 
					}else{
					$result['connection_status'] = true;
					$result['message'] = 'Connection Successfully. <br/> Directory Path : '.$_connection->pwd().$directory_path;
					$result['directory_path'] = $_connection->pwd();
					$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
					$resultJson->setData($result);
					return $resultJson;
					}
					
				}else if(($data['protocol']=='ftp') && (isset($data['host']) && isset($data['username']) && isset($data['password']))){
					
					$host = $data['host'];
					$user = $data['username'];
					$pass = $data['password'];
					$directory_path = $data['directory_path'];
					if (false !== strpos($host, ':')) {
						list($ftpHost, $ftpPort) = explode(':', $host);
					} else {
						$ftpHost = $host;
						$ftpPort = self::FTP_PORT;
					}
				
					$fileName = 'magebees-feed-tmp.tmp';
					$login_result = false;
					$login_result = $this->ftp->open(
					[
						'host' => $host,
						'port' => $ftpPort,
						'user' => $user,
						'password' => $pass,
						'path' => $directory_path
					]
				);

					 if (!$this->ftp->write($fileName, (string)__('Magebees Feed test connection file!'))) {
            			$this->ftp->close();
						 	$result['connection_status'] = false;
							$result['message'] = 'No write permissions.'; 
						 $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
						$resultJson->setData($result);
						return $resultJson;
        			}
					if($login_result){
						$result['connection_status'] = true;
						$result['message'] = 'Connection Successfully.';
						$this->ftp->rm($fileName);
						$this->ftp->close();
					}else{
						$result['connection_status'] = false;
						$result['message'] = 'Wrong Information, Not able to Conenct Server.'; 
					}
        			
					

					$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
					$resultJson->setData($result);
					return $resultJson;
					
				}else{
					$result['connection_status'] = false;
					$result['message'] = 'Required Information is Missing For Connection.'; 
					$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
					$resultJson->setData($result);
					return $resultJson;
				}
				
			
				$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
				$resultJson->setData($result);
				return $resultJson;
			} catch (\Exception $e) {
			$this->messageManager->addError(
                    __($e->getMessage())
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
			}
		}
		
	}
}
