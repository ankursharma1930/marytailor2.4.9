<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Ui\Component\Form\Element\DataType;

class Date extends \Magento\Ui\Component\Form\Element\DataType\Date
{
    /**
     * @inheritdoc
     */
    public function prepare()
    {
        $origConfig = $this->getData('config');
        parent::prepare();
        if (!empty($origConfig['options']['timeFormat'])) {
            $config = $this->getData('config');
            $config['options']['timeFormat'] = $origConfig['options']['timeFormat'];
            $this->setData('config', $config);
        }
    }
}
