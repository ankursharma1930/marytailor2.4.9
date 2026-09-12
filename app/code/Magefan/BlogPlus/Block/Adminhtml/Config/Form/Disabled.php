<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogPlus\Block\Adminhtml\Config\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Info
 */
class Disabled extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param AbstractElement $element
     * @return mixed
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setDisabled('disabled');
        return parent::_getElementHtml($element);
    }
}
