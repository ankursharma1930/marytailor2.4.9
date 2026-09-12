<?php
namespace Dolphin\Productfaq\Model;

use Magento\Framework\Data\OptionSourceInterface;

class Group implements OptionSourceInterface
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
                'label' => 'Specific Product',
                'value' => '0',
            ],
            [
                'label' => 'General',
                'value' => '1',
            ],
        ];
        return $options;
    }
}
