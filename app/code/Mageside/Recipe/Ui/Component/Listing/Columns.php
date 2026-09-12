<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface as AttributeMetadata;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Customer\Ui\Component\Listing\AttributeRepository;

class Columns extends \Magento\Ui\Component\Listing\Columns
{
    /** @var int */
    protected $columnSortOrder;

    /**
     * @var \Magento\Customer\Ui\Component\Listing\AttributeRepository
     */
    protected $attributeRepository;

    /**
     * Columns constructor.
     * @param ContextInterface $context
     * @param \Magento\Customer\Ui\Component\Listing\AttributeRepository $attributeRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        AttributeRepository $attributeRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        foreach ($this->attributeRepository->getList() as $newAttributeCode => $attributeData) {
            if (isset($this->components[$newAttributeCode])) {
                $this->updateColumn($attributeData);
            }
        }
        parent::prepare();
    }

    /**
     * @param array $attributeData
     */
    public function updateColumn(array $attributeData)
    {
        $component = $this->components[$attributeData[AttributeMetadata::ATTRIBUTE_CODE]];
        $this->addOptions($component, $attributeData);
    }

    /**
     * Add options to component
     *
     * @param UiComponentInterface $component
     * @param array $attributeData
     * @return void
     */
    public function addOptions(UiComponentInterface $component, array $attributeData)
    {
        $config = $component->getData('config');
        if (!empty($attributeData[AttributeMetadata::OPTIONS]) && !isset($config[AttributeMetadata::OPTIONS])) {
            $component->setData(
                'config',
                array_merge($config, [AttributeMetadata::OPTIONS => $attributeData[AttributeMetadata::OPTIONS]])
            );
        }
    }
}
