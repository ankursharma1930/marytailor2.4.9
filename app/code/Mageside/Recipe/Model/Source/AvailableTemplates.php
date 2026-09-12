<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model\Source;

/**
 * Class AvailableTemplates
 * @package Mageside\Recipe\Model\Source
 */
class AvailableTemplates implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Email\Model\Template\Config
     */
    private $_emailConfig;

    /**
     * AvailableTemplates constructor.
     * @param \Magento\Email\Model\Template\Config $emailConfig
     */
    public function __construct(\Magento\Email\Model\Template\Config $emailConfig)
    {
        $this->_emailConfig = $emailConfig;
    }

    /**
     * @param bool $withGroup
     * @return \array[]
     */
    public function toOptionArray($withGroup = false)
    {
        $options = $this->_emailConfig->getAvailableTemplates();

        uasort(
            $options,
            function (array $firstElement, array $secondElement) {
                return strcmp($firstElement['label'], $secondElement['label']);
            }
        );
        return $options;
    }
}
