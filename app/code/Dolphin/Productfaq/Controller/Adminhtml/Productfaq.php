<?php

namespace Dolphin\Productfaq\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\Page;

abstract class Productfaq extends Action
{
    public const ADMIN_RESOURCE = 'Dolphin_Productfaq::top_level';
    
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param Page $resultPage
     * @return Page
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Dolphin'), __('Dolphin'))
            ->addBreadcrumb(__('Productfaq'), __('Productfaq'));
        return $resultPage;
    }
}
