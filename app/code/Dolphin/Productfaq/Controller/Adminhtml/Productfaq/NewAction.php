<?php

namespace Dolphin\Productfaq\Controller\Adminhtml\Productfaq;

use Dolphin\Productfaq\Controller\Adminhtml\Productfaq;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\Model\View\Result\Redirect;

class NewAction extends Productfaq
{
    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context, $coreRegistry);
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Check if the current user has permission to perform the action
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Execute mass action.
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultForward = $this->resultForwardFactory->create();
        
        return $resultForward->forward('edit');
    }
}
