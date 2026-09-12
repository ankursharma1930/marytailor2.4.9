<?php
namespace Magebees\Productfeed\Block\Adminhtml\Mapping\Edit\Renderer;

/**
 * CustomFormField Customformfield field renderer
 */
class MappingContent extends \Magento\Framework\Data\Form\Element\AbstractElement
{
 
    /**
     * Get the after element html.
     *
     * @return mixed
     */
    public function getElementHtml()
    {
        $mappingDiv = '<div id="cat_mapping" class="category-mapping-content"></div>';
        return $mappingDiv;
    }
}
