<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Writer;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Mageside\Recipe\Model\WriterFactory
     */
    protected $_writerFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Mageside\Recipe\Model\WriterFactory $writerFactory,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_writerFactory = $writerFactory;
        $this->_coreRegistry = $coreRegistry;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $writer = $this->_writerFactory->create();
        if ($customerId = $this->getRequest()->getParam('customer_id')) {
            $writer->load($customerId, 'customer_id');
        }

        if ($writer->getCustomerId() && $writer->getIsWriter()) {
            $this->_coreRegistry->register('writer', $writer);
        } else {
            return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD)->forward('noroute');
        }

        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        if ($this->getRequest()->isAjax()) {
            $content = $resultPage->getLayout()->getBlock('recipe_list')->toHtml();
            return $this->resultFactory
                ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
                ->setData(['recipes' => $content]);
        }

        return $resultPage;
    }
}
