<?php
namespace Magebees\Productfeed\Block\Adminhtml\Feed\Edit\Renderer;

/**
 * CustomFormField Customformfield field renderer
 */
class TestConnection extends \Magento\Framework\Data\Form\Element\AbstractElement
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
        $mappingDiv = '<button title="Test Connection" type="button" class="scalable add testconnection" style=""><span>Test Connection</span></button><br/><img id="show_loader" style="display:none;"><div id="messages"><div class="messages"><div class="message message-success success" style="display:none;"></div><div class="message message-error error" style="display:none;"></div></div></div>';


        
        return $mappingDiv;
    }
}
