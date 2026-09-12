<?php
namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Renderer;

/**
 * CustomFormField Customformfield field renderer
 */
class CategoryContent extends \Magento\Framework\Data\Form\Element\AbstractElement
{
 
    /**
     * Get the after element html.
     *
     * @return mixed
     */
  
    
    public function getElementHtml()
    {
        $image_url = $this->getViewFileUrl('Magebees_Productfeed::images/loader.gif');
        // here you can write your code.
        $mappingDiv = '<div id="cat_content" class="category-mapping-content"><h1>Loading....</h1></div>';
        
        return $mappingDiv;
    }
}
