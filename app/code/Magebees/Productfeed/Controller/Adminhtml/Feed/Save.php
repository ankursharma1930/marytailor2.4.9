<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Controller\Adminhtml\Feed;

class Save extends \Magebees\Productfeed\Controller\Adminhtml\Feed
{
    public function execute()
    {
        
        if ($this->getRequest()->getPostValue()) {
            try {
                $model = $this->_objectManager->create('Magebees\Productfeed\Model\Feed');
                $data = $this->getRequest()->getPostValue();
                
                $inputFilter = new \Magento\Framework\Filter\FilterInput(
                    [],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();
                
                $id = $this->getRequest()->getParam('id');
                
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong feed is specified.'));
                    }
                }
                if ($data['type']=="xml") {
                 
					$xml_header = trim($data['xml']['xml_header']);
                    $item_start_tag = trim($data['xml']['feed_xml_item']);
                    $item_end_tag = trim($data['xml']['feed_xml_item']);
                    $item_tag = (explode("=", $item_start_tag));
                    if (isset($item_tag[0]) && ($item_tag[0]!="")) {
                        $tag = (explode(" ", $item_tag[0]));
                        $item_start_tag = trim($data['xml']['feed_xml_item']);
                        $item_end_tag = $tag[0];
                    }
                    
                    $xml_body = trim($data['xml']['xml_body']);
                    $xml_footer = trim($data['xml']['xml_footer']);
                    $xml_content = trim($data['xml']['xml_header'])."\n<".$item_start_tag.">\n".$xml_body."\n</".$item_end_tag.">\n".$xml_footer;
                    
					$filesystem = $this->_objectManager->get('Magento\Framework\Filesystem');
					$reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
                    
					$scope_info = $this->scopeConfig->getValue('productfeed/general',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
					if(!empty($scope_info['file_path']))
					{
					$file_path = $scope_info['file_path'];
					}else{
					$file_path = "var/export/";
					}
					
					$path = $reader->getAbsolutePath($file_path);
                    $filename = $data['name'];

					if (!file_exists($reader->getAbsolutePath().$file_path)) {
					mkdir($reader->getAbsolutePath().$file_path, 0777, true);
					}
					$tempFile = fopen($path.$filename."-content.xml", "w");
                    fwrite($tempFile, $xml_content);
                    
                    
                     fclose($tempFile);
                
				}
                
                
                if (isset($data['storeview'])) {
                    $data['storeview'] = $data['storeview']['0'];
                }
                if ($data['type']=="csv") {
                    if (isset($data['csv']['name'])) {
                        if (array_filter($data['csv']['name'])) {
                            $data['content'] = json_encode($data['csv']);
                        }
                    } else {
                        $data['content'] = '';
                    }
                } elseif ($data['type']=="xml") {
                    /* Remove Extra space from the xml content*/
                    $xml_content = null;
                    foreach ($data['xml'] as $key => $value) :
                        $xml_content[$key] = trim($value);
                    endforeach;
                    $data['content'] = json_encode($xml_content);
                }
                
                $download_url_info = [];
                if ($model->getId()) {
                    $download_url_info = $model->getDownloadUrl();
					
                    if ($this->isJSON($download_url_info)) {
                        $download_url_info = json_decode($download_url_info, true);
                        if (isset($data['feedname'])) {
                            $feedname=preg_replace('/\s+/', '', $data['feedname']);
                            $feedname = explode(".", $feedname);
                            $download_url_info['feedname'] = $feedname[0].".".$data['type'];
                        }
                    }
                } else {
                    if (isset($data['feedname'])) {
                        $feedname=preg_replace('/\s+/', '', $data['feedname']);
                        $download_url_info['feedname'] = $feedname.".".$data['type'];
						$download_url_info['feedstatus'] = 0;
                    }
                }
                
                if (!empty($data['categories'])) {
                    foreach ($data['categories'] as $cat_id => $cat_val) :
                        $data['conditions']['categories'][] = $cat_id;
                    endforeach;
                }
                
                
                if ($data['enable_schedule']==1) {
                    $feed_schedule_time_status = [];
                    if (isset($data['feedschedule']['times'])) {
                        foreach ($data['feedschedule']['times'] as $timeKey => $timeValue) :
                            $feed_schedule_time_status[$timeValue] = ['status'=>'0'];
                        endforeach;
                    }
                    if (count($feed_schedule_time_status)>0) {
                        $data['feedschedule']['feedstatus'] = $feed_schedule_time_status;
                    }
                    if (isset($data['feedschedule'])) {
                        $data['schedule'] = json_encode($data['feedschedule']);
                    }
                }
                if(!$data['format']['dateformat'])
				{
						$data['format']['dateformat'] = 'Y/m/d';
					
				}
				if(!$data['format']['timeformat'])
				{
						$data['format']['timeformat'] = 'H:i:s';
				}
                
                $data['format_serialized'] = json_encode($data['format']);
                $data['conditions_serialized'] = json_encode($data['conditions']);
                $data['download_url'] =json_encode($download_url_info);
                $data['ftp_settings'] = json_encode($data['ftp_settings']); 
				
                $model->setData($data);
                $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                $session->setPageData($model->getData());
                $model->save();
                $this->messageManager->addSuccess(__('You saved the feed.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('magebees_productfeed/*/edit', ['id' => $model->getId()]);
                    return;
                }
                 
                $this->_redirect('magebees_productfeed/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('magebees_productfeed/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('magebees_productfeed/*/new');
                }
                return;
            } catch (\Exception $e) {
                /*$this->messageManager->addError(
                    __('Something went wrong while saving the feed data. Please review the error log.')
                );*/
                $this->messageManager->addError(
                    __($e->getMessage())
                );
                
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('magebees_productfeed/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('magebees_productfeed/*/');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Productfeed::feed');
    }
    
    public function isJSON($string)
    {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
