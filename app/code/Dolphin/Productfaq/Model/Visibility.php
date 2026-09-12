<?php
namespace Dolphin\Productfaq\Model;

use Magento\Framework\Data\OptionSourceInterface;

class Visibility implements OptionSourceInterface
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
                'label' => 'Public',
                'value' => '0',
            ],
            [
                'label' => 'Private',
                'value' => '1',
            ],
        ];
        return $options;
    }
}
