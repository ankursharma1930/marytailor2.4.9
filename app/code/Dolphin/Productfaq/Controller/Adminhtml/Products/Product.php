<?php
namespace Dolphin\Productfaq\Controller\Adminhtml\Products;

use Magento\Backend\App\Action;

abstract class Product extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dolphin_Productfaq::item_list';
}
