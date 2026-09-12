<?php
namespace  Magebees\Productfeed\Block\Adminhtml\Templates\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;

class Content extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_mappingCollection;
    protected $_feedModelTemplate;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magebees\Productfeed\Model\ResourceModel\Mapping\Collection $mappingCollection,
        \Magebees\Productfeed\Model\Templates $feedModelTemplate,
        array $data = []
    ) {
        $this->_feedModelTemplate = $feedModelTemplate;
        $this->_systemStore = $systemStore;
        $this->_mappingCollection = $mappingCollection;
        parent::__construct($context, $registry, $formFactory, $data);
         $this->setTemplate('templatecontent.phtml');
    }
    public function getTabLabel()
    {
        return __('Template Content');
    }
    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Template Content');
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
    public function getCategoryMappingCollection()
    {
        return $this->_mappingCollection;
    }
    public function getFeedModelTemplate()
    {
        return $this->_feedModelTemplate;
    }
}
