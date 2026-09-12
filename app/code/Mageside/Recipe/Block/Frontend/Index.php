<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend;

class Index extends \Mageside\Recipe\Block\Frontend\AbstractBlock
{
    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Writer\Collection
     */
    protected $writerCollection;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * Index constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     * @param \Mageside\Recipe\Helper\Config $helper
     * @param \Mageside\Recipe\Model\ResourceModel\Writer\Collection $writerCollection
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Mageside\Recipe\Model\FileUploader $fileUploader,
        \Mageside\Recipe\Helper\Config $helper,
        \Mageside\Recipe\Model\ResourceModel\Writer\Collection $writerCollection,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->writerCollection = $writerCollection;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $fileUploader, $helper);
    }

    /**
     * @return AbstractBlock
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__($this->helper->getSeoTitle()));

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
                ['label' => __($this->helper->getSeoTitle())]
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * @return $this
     */
    public function getWritersCollection()
    {
        $collectionCustomer = $this->writerCollection->addWriterFilter();

        if ($collectionCustomer->getSize()) {
            $this->coreRegistry->register('writers', $collectionCustomer);
        }

        return $collectionCustomer;
    }
}
