<?php
namespace Magebees\Productfeed\Block\Adminhtml\Dynamicattributes\Edit\Renderer;

/**
 * CustomFormField Customformfield field renderer
 */
class Conditions extends \Magento\Framework\Data\Form\Element\AbstractElement
{
 
    /**
     * Get the after element html.
     *
     * @return mixed
     */
    public function getElementHtml()
    {
    
        $mappingDiv = '<div id="condition_mapping" class="condition-mapping-content"></div>';
        return $mappingDiv;
    }
}
