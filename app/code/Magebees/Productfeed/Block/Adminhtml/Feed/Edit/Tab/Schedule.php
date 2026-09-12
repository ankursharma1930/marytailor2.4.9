<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

// @codingStandardsIgnoreFile

namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Tab;


use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Schedule extends Generic implements TabInterface
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
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Feed Schedule Information')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
			$schedule_info = $model->getSchedule();
			$informations = $model->getData();
			
			if($this->isJSON($schedule_info)){
				$schedule_info = json_decode($schedule_info, true);
				foreach($schedule_info as $subkey => $subvalue):
						$informations[$subkey] = $subvalue;	
					endforeach;
			}
			$days = array();
			$times = array();
			
	   }else{
			$informations = array();
			$informations['enable_schedule'] = '';
			$informations['days'] = '';
			$informations['times'] = '';
			$days = array();
			$times = array();
			
			
		}
		
        
		$fieldset->addField(
            'enable_schedule',
            'select',
			['name' => 'enable_schedule', 
			'label' => __('Enable Schedule'),
			'title' => __('Enable Schedule'),
			'note' => 'If enabled, extension will generate feed by schedule. To generate feed by schedule, magento cron must be configured.',
			'values'=> $this->_yesno->toOptionArray()
			]
        );
		
		
		$fieldset->addField(
            'days',
            'multiselect',
			['name' => 'feedschedule[days][]', 
			'label' => __('Days of the week'), 
			'title' => __('Days of the week'), 
			'values'=> $this->_templates->getDays(),
			'value'=> $days,
			]
        );
		
		$fieldset->addField(
            'times',
            'multiselect',
			['name' => 'feedschedule[times][]', 
			'label' => __('Time of the day'), 
			'title' => __('Time of the day'), 
			'values'=> $this->_templates->getTime(),
			'value'=> $times,]
        );
	if (!$model->getId()) {
		$form->setValues($informations);
	}else{
		
		$form->setValues($informations);
	}
	   
        $this->setForm($form);
        return parent::_prepareForm();
    }
	public function isJSON($string){
			return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
		}
}
