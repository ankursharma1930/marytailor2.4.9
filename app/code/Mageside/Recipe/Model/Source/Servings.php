<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\Source;

class Servings implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $options = [];
        for ($i = 0; $i < 24; $i++) {
            $options[] = [
                'label' => strval($i),
                'value' => strval($i)
            ];
        }
        return $options;
    }
}
