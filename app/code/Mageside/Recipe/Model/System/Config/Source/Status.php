<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\System\Config\Source;

use Magento\Review\Helper\Data as StatusSource;

class Status implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    private $_options;


    /**
     * @var StatusSource
     */
    protected $source;

    protected $_reviewData = null;

    /**
     * Status constructor.
     * @param StatusSource $source
     */
    public function __construct(
        \Magento\Review\Helper\Data $reviewData,
        StatusSource $source
    ) {
        $this->_reviewData = $reviewData;
        $this->source = $source;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $status = $this->source->getReviewStatuses();

            $options = [];
            foreach ($status as $key => $value) {
                $options[] = [
                    'value' => $key,
                    'label' => $value
                ];
            }
            $this->_options = $options;
        }

        return $this->_options;
    }
}
