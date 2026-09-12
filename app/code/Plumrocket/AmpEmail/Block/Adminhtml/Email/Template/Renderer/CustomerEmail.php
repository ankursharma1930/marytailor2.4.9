<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Adminhtml\Email\Template\Renderer;

/**
 * @method setElementParams(array $array)
 */
class CustomerEmail extends \Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element
{
    /**
     * @var string
     */
    protected $_template = 'Plumrocket_AmpEmail::widget/form/renderer/customer_email.phtml'; //@codingStandardsIgnoreLine

    /**
     * @param $paramName
     * @return mixed
     */
    public function getElementParams(string $paramName) : string
    {
        return (string) $this->getData('element_params/' . $paramName);
    }
}
