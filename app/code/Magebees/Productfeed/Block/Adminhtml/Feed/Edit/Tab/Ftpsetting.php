<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

// @codingStandardsIgnoreFile

namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab;


use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Ftpsetting extends Generic implements TabInterface
{

    /**
     * {@inheritdoc}
     */
	protected $_templates;
	protected $_systemStore;
	protected $_yesno;
	protected $_objectManager;
	protected $_filesystem;
	 public function __construct(
		\Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
		\Magento\Config\Model\Config\Source\Yesno $yesno,
		\Magebees\Productfeed\Model\Templates $templates,
		\Magebees\Productfeed\Model\Feed $feed,
		array $data = array()
    ) {
        $this->_systemStore = $systemStore;
		$this->_filesystem = $context->getFilesystem();
        $this->_templates = $templates;
		$this->_yesno = $yesno;
		$this->feed = $feed;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    public function getTabLabel()
    {
        return __('Feed Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Feed Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_magebees_productfeed_feed');
		$isElementDisabled = false;
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('FTP Information')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
			$ftp_settings = $model->getFtpSettings();
			$informations = $model->getData();
			
			if($this->isJSON($ftp_settings)){
				$ftp_settings = json_decode($ftp_settings, true);
				foreach($ftp_settings as $subkey => $subvalue):
						$informations[$subkey] = $subvalue;	
					endforeach;
			}
		}else{
			$informations = array();
			$informations['enable_ftp_upload'] = '';
			$informations['protocol'] = '';
			$informations['use_active_mode'] = '';
			$informations['host'] = '';
			$informations['username'] = '';
			$informations['password'] = '';
			$informations['directory'] = '';
		}
		$enable_ftp_upload = $fieldset->addField(
            'enable_ftp_upload',
            'select',
			
			['name' => 'ftp_settings[enable_ftp_upload]', 
			'label' => __('Enable FTP upload'),
			'title' => __('Enable FTP upload'),
			'required' => true,
			'note' => 'If enabled, Generated feed file will be uploaded on the server.',
			'values'=> $this->_yesno->toOptionArray()
			]
        );
		
		$protocol_value = array(
		'ftp' => 'FTP',
		'sftp' => 'SFTP'
		);
		
		$protocol = $fieldset->addField(
            'protocol',
            'select',
			['name' => 'ftp_settings[protocol]', 
			'label' => __('Protocol'), 
			'title' => __('Protocol'), 
			  'required' => true,
			 'values'=> $protocol_value
			]
        );
		$host = $fieldset->addField(
            'host',
            'text',
			['name' => 'ftp_settings[host]', 
			'label' => __('Host Name'), 
			'title' => __('Host Name'), 
			  'required' => true,
			 'note' => 'Add port if necessary (example.com:21).<br/> For SFTP Add Port 22',
			]
        );
		$username = $fieldset->addField(
            'username',
            'text',
			['name' => 'ftp_settings[username]', 
			'label' => __('User Name'), 
			'title' => __('User Name'),
			  'required' => true,
			]
        );
		$password = $fieldset->addField(
            'password',
            'password',
			['name' => 'ftp_settings[password]', 
			'label' => __('Password'), 
			'title' => __('Password'),
			  'required' => true,
			]
        );
		$use_active_mode = $fieldset->addField(
            'use_active_mode',
            'select',
			['name' => 'ftp_settings[use_active_mode]', 
			'label' => __('Use active mode'),
			'title' => __('Use active mode'),
			 'required' => true,
			'values'=> $this->_yesno->toOptionArray()
			]
        );
		$directory_path = $fieldset->addField(
            'directory_path',
            'text',
			['name' => 'ftp_settings[directory_path]', 
			'label' => __('Destination directory'), 
			'title' => __('Destination directory'), 
			  'required' => true,
			 'note' => 'Please Create Folder In the FTP/SFTP.',
			]
        );
		
		$fieldset->addType(
        'test_connection',
        '\Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Renderer\TestConnection'
    );
  $connection =   $fieldset->addField(
        'connection',
        'test_connection',
        [
            'name'  => 'connection',
            'label' => __('Test Connection'),
            'title' => __('Test Connection'),
           
        ]
    );
		
	if (!$model->getId()) {
		$form->setValues($informations);
	}else{
		
		$form->setValues($informations);
	}
	   
		$this->setChild('form_after', $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Form\Element\Dependence')
            ->addFieldMap($enable_ftp_upload->getHtmlId(), $enable_ftp_upload->getName())
			->addFieldMap($protocol->getHtmlId(), $protocol->getName())
            ->addFieldMap($use_active_mode->getHtmlId(), $use_active_mode->getName())
			->addFieldMap($host->getHtmlId(), $host->getName())
			->addFieldMap($username->getHtmlId(), $username->getName())
			->addFieldMap($password->getHtmlId(), $password->getName())
			->addFieldMap($directory_path->getHtmlId(), $directory_path->getName())
			->addFieldMap($connection->getHtmlId(), $connection->getName())
			->addFieldDependence(
                $protocol->getName(),
                $enable_ftp_upload->getName(),
                '1'
            )
			->addFieldDependence(
                $use_active_mode->getName(),
                $enable_ftp_upload->getName(),
                '1'
            )
			->addFieldDependence(
                $host->getName(),
                $enable_ftp_upload->getName(),
                '1'
            )
			->addFieldDependence(
                $username->getName(),
                $enable_ftp_upload->getName(),
                '1'
            )
			->addFieldDependence(
                $password->getName(),
                $enable_ftp_upload->getName(),
                '1'
            )
			->addFieldDependence(
                $directory_path->getName(),
                $enable_ftp_upload->getName(),
                '1'
            )
			->addFieldDependence(
                $connection->getName(),
                $enable_ftp_upload->getName(),
                '1'
            )
			);
		$this->setForm($form);
        return parent::_prepareForm();
    }
	public function isJSON($string){
			return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
		}
}
