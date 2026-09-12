<?php
namespace  Magebees\Productfeed\Block\Adminhtml\Dynamicattributes\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;

class Content extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $templates;
    protected $_dynamicattributes;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magebees\Productfeed\Model\Dynamicattributes $dynamicattributes,
        \Magebees\Productfeed\Model\Templates $templates,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_dynamicattributes = $dynamicattributes;
        $this->_templates = $templates;
        parent::__construct($context, $registry, $formFactory, $data);
         $this->setTemplate('dynamicattributes.phtml');
    }
    public function getTabLabel()
    {
        return __('Dynamic Attributes');
    }
    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Dynamic Attributes');
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
    public function getTemplates()
    {
        return  $this->_templates;
    }
    public function getDynamicattributes()
    {
        return $this->_dynamicattributes;
    }
}
