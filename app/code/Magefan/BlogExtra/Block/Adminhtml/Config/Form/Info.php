<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Block\Adminhtml\Config\Form;

/**
 * Class Info
 */
class Info extends \Magefan\Community\Block\Adminhtml\System\Config\Form\Info
{
    /**
     * Return info block html
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $version = $this->getModuleVersion->execute($this->getModuleName());
        $html = '<div style="padding:10px;background-color:#f8f8f8;border:1px solid #ddd;margin-bottom:7px;">
            + Addon <strong>Blog Extra v' . $this->escapeHtml($version) . '</strong>
        </div>';

        return $html;
    }
}
