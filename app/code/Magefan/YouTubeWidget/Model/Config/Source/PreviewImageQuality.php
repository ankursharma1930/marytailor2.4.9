<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\YouTubeWidget\Model\Config\Source;

class PreviewImageQuality  implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'mqdefault', 'label' => __('Medium Quality (320×180 pixels)')],
            ['value' => 'hqdefault', 'label' => __('High Quality (480×360 pixels)')],
            ['value' => 'sddefault', 'label' => __('Standard Definition (640×480 pixels)')],
            ['value' => 'maxresdefault', 'label' => __('Maximum Resolution (1920×1080 pixels)')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }
}