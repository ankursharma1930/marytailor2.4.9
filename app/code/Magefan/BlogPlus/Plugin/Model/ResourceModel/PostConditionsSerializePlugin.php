<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel;

/**
 * Class PostConditionsSerializePlugin
 */
class PostConditionsSerializePlugin
{
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    public $ruleFactory;

    /**
     * PostConditionsSerializePlugin constructor.
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     */
    public function __construct(
        \Magento\SalesRule\Model\RuleFactory  $ruleFactory
    ) {
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Serialize related product rule conditions
     * @param \Magefan\Blog\Model\ResourceModel\Post $subject
     * @param $object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave(
        \Magefan\Blog\Model\ResourceModel\Post $subject,
        $object
    ) {
        if ($object->getRule('conditions')) {
            $rule = $this->ruleFactory->create();
            $rule->loadPost(['conditions' => $object->getRule('conditions')]);
            $rule->beforeSave();
            $object->setData(
                'rp_conditions_serialized',
                $rule->getConditionsSerialized()
            );
        }
    }
}
