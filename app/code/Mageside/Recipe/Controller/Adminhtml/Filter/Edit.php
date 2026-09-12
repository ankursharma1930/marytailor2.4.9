<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Filter;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    /**
     * @var \Mageside\Recipe\Model\Recipe\FilterFactory
     */
    protected $_filterFactory;

    /**
     * Edit constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mageside\Recipe\Model\Recipe\FilterFactory $filterFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mageside\Recipe\Model\Recipe\FilterFactory $filterFactory
    ) {
        $this->_filterFactory = $filterFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Mageside_MultipleCustomForms::recipe');

        $id = $this->getRequest()->getParam('id');
        $filter = $this->_filterFactory->create()->load($id);
        if ($filter->getId()) {
            $resultPage->getConfig()->getTitle()->prepend(__('Edit Filter Id: %1', $id));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Filter'));
        }

        return $resultPage;
    }
}
