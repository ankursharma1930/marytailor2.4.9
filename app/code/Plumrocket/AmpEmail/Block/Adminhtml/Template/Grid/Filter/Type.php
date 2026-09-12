<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Adminhtml\Template\Grid\Filter;

use Plumrocket\AmpEmail\Helper\Config as ConfigHelper;

/**
 * Class Type
 *
 * @package Plumrocket\AmpEmail\Block\Adminhtml\Template\Grid\Filter
 * @method mixed getValue()
 */
class Type extends \Magento\Email\Block\Adminhtml\Template\Grid\Filter\Type
{
    /**
     * @var \Plumrocket\AmpEmail\Api\AmpTemplateProviderInterface
     */
    private $ampTemplateProvider;

    /**
     * @var \Magento\Email\Model\ResourceModel\Template\CollectionFactory
     */
    private $templateCollectionFactory;

    /**
     * Type constructor.
     *
     * @param \Magento\Backend\Block\Context                                $context
     * @param \Magento\Framework\DB\Helper                                  $resourceHelper
     * @param \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory
     * @param \Plumrocket\AmpEmail\Api\AmpTemplateProviderInterface         $ampTemplateProvider
     * @param array                                                         $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory,
        \Plumrocket\AmpEmail\Api\AmpTemplateProviderInterface $ampTemplateProvider,
        array $data = []
    ) {
        parent::__construct($context, $resourceHelper, $data);

        $this->ampTemplateProvider = $ampTemplateProvider;
        $this->templateCollectionFactory = $templateCollectionFactory;
    }

    /**
     * @return array
     */
    protected function _getOptions() //@codingStandardsIgnoreLine
    {
        $result = [];
        $types = parent::$_types;

        $types[ConfigHelper::AMP_TYPE] = 'AMP';

        foreach ($types as $code => $label) {
            $result[] = ['value' => $code, 'label' => __($label)];
        }

        return $result;
    }

    /**
     * @return array|null
     */
    public function getCondition()
    {
        if (null === $this->getValue()) {
            return null;
        }

        /** @var \Magento\Email\Model\ResourceModel\Template\Collection $templateCollection */
        $templateCollection = $this->templateCollectionFactory->create();

        $templateIds = [];

        if (ConfigHelper::AMP_TYPE === (int) $this->getValue()) {
            foreach ($templateCollection as $template) {
                if ($this->ampTemplateProvider->isExistAmpForEmailById($template->getId())) {
                    $templateIds[] = $template->getId();
                }
            }
        } else {
            $templateIds = $templateCollection
                ->addFieldToFilter('template_type', $this->getValue())
                ->getAllIds();
        }

        return ['in' => $templateIds];
    }
}
