<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class DeleteButton
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [
            'label' => $this->getButtonLabel(),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __('Are you sure you wan\\\'t to delete this record?') .
                '\', \'' .
                $this->getDeleteUrl() .
                '\')',
            'sort_order' => 20,
        ];

        return $data;
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('recipe/*/delete', ['id' => $this->getRequest()->getParam('recipe_id')]);
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getButtonLabel()
    {
        return __('Delete');
    }
}
