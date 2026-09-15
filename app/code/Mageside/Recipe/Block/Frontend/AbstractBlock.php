<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Frontend;

class AbstractBlock extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Mageside\Recipe\Model\FileUploader
     */
    protected $fileUploader;

    /**
     * @var \Mageside\Recipe\Helper\Config
     */
    protected $helper;

    /**
     * AbstractBlock constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mageside\Recipe\Model\FileUploader $fileUploader
     * @param \Mageside\Recipe\Helper\Config $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Mageside\Recipe\Model\FileUploader $fileUploader,
        \Mageside\Recipe\Helper\Config $helper,
        array $data = []
    ) {
        $this->fileUploader = $fileUploader;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getImageUrl()
    {
        $imagePath = $this->fileUploader->getBaseUrl() . $this->fileUploader->getBasePath();

        return $imagePath;
    }

    /**
     * @return string
     */
    public function getRecipePath()
    {
        return $this->fileUploader->getBasePath();
    }

    /**
     * @param $string
     * @return int|mixed
     */
    public function getFormatServingsNumber($string)
    {
        $servings = array_map('trim', explode('-', $string));
        $min = (int) min($servings);
        $max = (int) max($servings);

        if ($min == $max) {
            if ($max) {
                return $max;
            } else {
                return 1;
            }
        } else {
            if ($min) {
                return $min . ' - ' . $max;
            } else {
                return $max;
            }
        }
    }

    /**
     * @param $time
     * @return bool|mixed
     */
    public function getFormatCookTime($time)
    {
        if ($time) {
            return str_replace(':', $this->escapeHtml('h').' ', $time);
        }

        return false;
    }

    /**
     * Human readable duration for an "HH:mm" value, e.g. "1 hr 30 min"
     *
     * @param string $time
     * @return string empty when the time is not set or is zero
     */
    public function getFormattedDuration($time)
    {
        if (!preg_match('/^(\d+):(\d+)$/', trim((string) $time), $matches)) {
            return '';
        }

        $parts = [];
        if ($hours = (int) $matches[1]) {
            $parts[] = __('%1 hr', $hours);
        }
        if ($minutes = (int) $matches[2]) {
            $parts[] = __('%1 min', $minutes);
        }

        return implode(' ', $parts);
    }

    /**
     * Servings for display, e.g. "4" or "2 - 4"
     *
     * @param string $servings stored as "min - max"
     * @return string empty when servings were never entered ("0 - 0")
     */
    public function getServings($servings)
    {
        return preg_match('/[1-9]/', (string) $servings)
            ? (string) $this->getFormatServingsNumber($servings)
            : '';
    }

    /**
     * @return string
     */
    public function getImageStub()
    {
        return $this->getImageUrl() . DIRECTORY_SEPARATOR . \Mageside\Recipe\Helper\Image::PICTURE_STUB;
    }
}
