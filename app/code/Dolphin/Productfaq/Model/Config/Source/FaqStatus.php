<?php
namespace Dolphin\Productfaq\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FaqStatus implements OptionSourceInterface
{
    /**
     * Generates an array of options for a dropdown select element.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'label' => 'Disable',
                'value' => '0',
            ],
            [
                'label' => 'Enable',
                'value' => '1',
            ],
        ];
        return $options;
    }
}
