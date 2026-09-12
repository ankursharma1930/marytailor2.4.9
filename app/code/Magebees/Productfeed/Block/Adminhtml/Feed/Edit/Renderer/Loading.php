<?php
namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Renderer;

/**
 * CustomFormField Customformfield field renderer
 */
class Loading extends \Magento\Framework\Data\Form\Element\AbstractElement
{
 
    /**
     * Get the after element html.
     *
     * @return mixed
     */
  
    public function getElementHtml()
    {
        $loading_content = "<div id='export_popup'><div class='steps-export' style='display:none'>
        <ul class='nav-bar'><li id='initialization'><a href='#'>Initialization</a></li>
	<li id='total_products'><a href='#'>Filtration</a></li><li id='exporting'><a href='#'>Exporting</a></li><li id='complate'><a href='#'>Finalization</a></li></ul><div class='clearfix'></div></div><div id='loading-icon'></div><div id='myProgress' style='display:none;'><div id='myBar'><div id='label'></div></div></div><div id='export_popup1'></div><div id='error_message' style='display:none;'></div></div>";
        return $loading_content;
    }
}
