<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend\Recipe;

class View extends \Mageside\Recipe\Block\Frontend\AbstractBlock
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customer;

    /**
     * View constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     * @param \Mageside\Recipe\Helper\Config $helper
     * @param \Magento\Customer\Model\CustomerFactory $customer
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Mageside\Recipe\Model\FileUploader $fileUploader,
        \Mageside\Recipe\Helper\Config $helper,
        \Magento\Customer\Model\CustomerFactory $customer,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_customer = $customer;
        parent::__construct($context, $fileUploader, $helper);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            )->addCrumb(
                'recipe_list',
                ['label' => __($this->helper->getSeoTitle()), 'link' => $this->getUrl($this->helper->getSeoRoute())]
            );
            if ($this->getWriter()) {
                $breadcrumbs->addCrumb(
                    'recipe_writer',
                    ['label' => __($this->getWriterName())]
                );
            }
        }

        return parent::_prepareLayout();
    }

    /**
     * @return mixed
     */
    public function getWriter()
    {
        return $this->_coreRegistry->registry('writer');
    }

    /**
     * @return string
     */
    public function getWriterAvatar()
    {
        return $this->getImageUrl() . DIRECTORY_SEPARATOR . $this->getWriter()->getAvatar();
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->getWriter()->getCustomerId();
    }

    /**
     * @return string
     */
    protected function getWriterName() {
        $writer = $this->_customer->create()->load($this->getCustomerId());

        return $writer->getFirstname() . ' ' . $writer->getLastname();
    }
}
