<?php
namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Renderer;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Store\Model\StoreManagerInterface;

/**
 * CustomFormField Customformfield field renderer
 */
class Info extends AbstractRenderer
{
    private $_storeManager;
    /**
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    protected $_objectManager;
    protected $_filesystem;
	
    public function __construct(
        \Magento\Backend\Block\Context $context,
        StoreManagerInterface $storemanager,
        \Magento\Framework\Filesystem $filesystem,
		array $data = []
    ) {
    
        $this->_storeManager = $storemanager;
        $this->_filesystem = $filesystem;
		parent::__construct($context, $data);
        $this->_authorization = $context->getAuthorization();
    }
    public function _getValue(\Magento\Framework\DataObject $row)
    {
        $value = parent::_getValue($row);
        if ($row->getDownloadUrl()!="") {
            $storeId = 0;
            $file_info = json_decode($row->getDownloadUrl(), true);
        //print_r($file_info);
            if (!empty($file_info['feedname'])) {
                $feedname = $file_info['feedname'];
                $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
                $scope_info = $this->_scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($scope_info['file_path']))
			{
			$file_path = $scope_info['file_path'];
			}else{
			$file_path = 'var/export/';
			}
				//$path = $reader->getAbsolutePath('export/');
				$path = $reader->getAbsolutePath($file_path);
                if (file_exists($path.$feedname)) {
                    $baseUrl = $this->getUrl('*/*/downloadexportedfile', ['file'=>$feedname]);
                    $value = "<a href=".$baseUrl." title=".$row->getName()." >".$feedname."</a>";
                    return $value;
                } else {
                    return "File Not Exists";
                }
            }
        }
        return $value;
    }
}
