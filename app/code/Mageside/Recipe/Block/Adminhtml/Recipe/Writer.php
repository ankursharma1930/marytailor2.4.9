<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Block\Adminhtml\Recipe;

class Writer extends \Magento\Backend\Block\Template
{
    /**
     * Path to template file in theme.
     * @var string
     */
    protected $_template = 'Mageside_Recipe::recipe/fieldset.phtml';

    /**
     * Recipe model
     * @var \Mageside\Recipe\Model\Recipe
     */
    protected $recipe;

    /**
     * @var
     */
    protected $writerId;

    /**
     * Core registry
     * @var \Magento\Framework\Registry
     */
    protected $registry = null;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Writer\Collection
     */
    protected $writerCollection;

    /**
     * @var null
     */
    protected $writerName = null;

    /**
     * @var bool
     */
    protected $isWriter = false;

    /**
     * Writer constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Mageside\Recipe\Model\Recipe $recipe
     * @param \Magento\Framework\Registry $registry
     * @param \Mageside\Recipe\Model\ResourceModel\Writer\Collection $writerCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Mageside\Recipe\Model\ResourceModel\Writer\Collection $writerCollection,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->writerCollection = $writerCollection;
        parent::__construct($context, $data);
    }

    /**
     * Getting URL for edit recipe
     * @return bool|string
     */
    public function getWriterUrl()
    {
        if ($this->getWriterId()) {
            $recipeUrl = $this->_urlBuilder->getUrl('customer/index/edit', ['id' => $this->getWriterId()]);

            return $recipeUrl;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isWriter()
    {
        $this->getWriterName();
        return $this->isWriter;
    }
    /**
     * Getting recipe name
     * @return bool|string
     */
    public function getWriterName()
    {
        if (!$this->writerName) {
            $this->isWriter = false;
            if ($this->getWriterId()) {
                $writer = $this->writerCollection->addFieldToFilter('customer_id', $this->getWriterId())
                    ->getWriterRecipe()
                    ->setPageSize(1)
                    ->getFirstItem();
                if ($writer->getId()) {
                    if ($writer->getIsWriter()) {
                        if ($writer->getId()) {
                            $this->isWriter = true;
                            if ($writer->getNickname()) {
                                $this->writerName = $writer->getNickname();
                                return $this->writerName;
                            } else {
                                $this->writerName = $writer->getName();
                                return $this->writerName;
                            }
                        }
                    }
                    return $this->writerName = __('Customer is not a writer');
                }
                return $this->writerName = __('Writer is not set');
            }
        }
        return $this->writerName;
    }

    /**
     * Getting recipe ID for later using
     * @return bool|int
     */
    protected function getWriterId()
    {
        if (!$this->writerId) {
            $this->writerId = false;
            if ($this->writerId = $this->getRequest()->getParam('customer_id')) {
                return $this->writerId;
            }
            if ($recipe = $this->registry->registry('current_recipe')) {
                if ($recipe->getCustomerId()) {
                    $this->writerId = $recipe->getCustomerId();
                } elseif ($this->registry->registry('new_review_data')) {
                    $this->writerId = $this->registry->registry('new_review_data')->getEntityPkValue();
                }
            }
        }

        return $this->writerId;
    }
}
