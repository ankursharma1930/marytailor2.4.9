<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */
namespace Magebees\Productfeed\Model;

use Magento\Framework\Component\ComponentRegistrar;

class Import extends \Magento\Framework\Model\AbstractModel
{
    const SAMPLE_MODULE_TEMPLATES = 'Magebees_Productfeed';
    
    protected $_filesystem;
    protected $_directoryList;
    protected $componentRegistrar;
    
    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar
    ) {
    
        $this->_filesystem = $filesystem;
        $this->_directoryList = $directoryList;
         $this->componentRegistrar = $componentRegistrar;
    }
    
    public function getImportedTemplateList()
    {

    
    
        
        $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $exported_templates_list = [];
        
        if ($reader->isExist('export/feedtemplate')) {
            $path = $reader->getAbsolutePath('export/feedtemplate');
            $exported_templates = scandir($path);
            
            $d = dir($path);
            while (false !== ($entry = $d->read())) {
                $filepath = "{$path}/{$entry}";
                if (is_file($filepath)) {
                    $filename = $entry;
                    $file_type = filetype($filepath);//get file type.
                    $file_size = filesize($filepath);//get file size.
                    $path_info = pathinfo($path."/".$filename);
            
                    if ($path_info['extension']=='json') {
                        $exported_templates_list[] = ['label' => $path_info['filename'].' [Created / Exported]','value' => $path."/".$filename];
                    }
                }
            }
        }
         $moduleDir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::SAMPLE_MODULE_TEMPLATES);
            $fileAbsolutePath = $moduleDir . '/Files/feedtemplate';
        if (scandir($fileAbsolutePath)) {
            $exported_templates = scandir($fileAbsolutePath);
            $d = dir($fileAbsolutePath);
            while (false !== ($entry = $d->read())) {
                $filepath = "{$fileAbsolutePath}/{$entry}";
                if (is_file($filepath)) {
                    $filename = $entry;
                    $file_type = filetype($filepath);//get file type.
                    $file_size = filesize($filepath);//get file size.
                    $path_info = pathinfo($fileAbsolutePath."/".$filename);
            
                    if ($path_info['extension']=='json') {
                        $exported_templates_list[] = ['label' => $path_info['filename'].' [Default]','value' => $fileAbsolutePath."/".$filename];
                    }
                }
            }
        }
        
        return $exported_templates_list;
    }
}
