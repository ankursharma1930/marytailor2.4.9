<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller\Adminhtml\Recipe;

class Writer extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageside_Recipe::mageside_recipe_manage';

    public function execute()
    {
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Mageside_Recipe::recipe');
        $resultPage->getConfig()->getTitle()->prepend(__('Writer'));

        return $resultPage;
    }
}
