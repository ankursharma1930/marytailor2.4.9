<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Adminhtml\Template\Grid\Filter;

use Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface;

/**
 * @method mixed getValue()
 */
class AmpStatus extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Select
{
    /**
     * @var \Magento\Email\Model\ResourceModel\Template\CollectionFactory
     */
    private $templateCollectionFactory;

    /**
     * @var \Plumrocket\AmpEmail\Api\AmpTemplateProviderInterface
     */
    private $ampTemplateProvider;

    /**
     * @var \Plumrocket\AmpEmail\Model\OptionSource\TemplateStatus
     */
    private $templateStatus;

    /**
     * @param \Magento\Backend\Block\Context                                $context
     * @param \Magento\Framework\DB\Helper                                  $resourceHelper
     * @param \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory
     * @param \Plumrocket\AmpEmail\Api\AmpTemplateProviderInterface         $ampTemplateProvider
     * @param \Plumrocket\AmpEmail\Model\OptionSource\TemplateStatus        $templateStatus
     * @param array                                                         $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory,
        \Plumrocket\AmpEmail\Api\AmpTemplateProviderInterface $ampTemplateProvider,
        \Plumrocket\AmpEmail\Model\OptionSource\TemplateStatus $templateStatus,
        array $data = []
    ) {
        parent::__construct($context, $resourceHelper, $data);
        $this->templateCollectionFactory = $templateCollectionFactory;
        $this->ampTemplateProvider = $ampTemplateProvider;
        $this->templateStatus = $templateStatus;
    }

    /**
     * @return array
     */
    protected function _getOptions() //@codingStandardsIgnoreLine
    {
        return [['value' => null, 'label' => null]] + $this->templateStatus->toOptionArray();
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

        try {
            switch ($this->getValue()) {
                case AmpTemplateInterface::AMP_EMAIL_STATUS_LIVE:
                    $templateIds = $this->getLiveTemplateIds($templateCollection);
                    break;
                case AmpTemplateInterface::AMP_EMAIL_STATUS_SANDBOX:
                    $templateIds = $this->getSandboxTemplateIds($templateCollection);
                    break;
                default:
                    $templateIds = $this->getDisabledTemplateIds($templateCollection);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $noSuchEntityException) {
            $templateIds = [];
        }

        return ['in' => $templateIds];
    }

    /**
     * @param \Magento\Email\Model\ResourceModel\Template\Collection $templateCollection
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getLiveTemplateIds(
        \Magento\Email\Model\ResourceModel\Template\Collection $templateCollection
    ) : array {
        $templateIds = [];
        foreach ($templateCollection as $template) {
            if ($this->ampTemplateProvider->isExistAmpForEmailById($template->getId())) {
                $ampTemplate = $this->ampTemplateProvider->getTemplate($template->getId());
                if ($ampTemplate->isEnabledAmpForEmail() && $ampTemplate->isAmpForEmailInLive()) {
                    $templateIds[] = $template->getId();
                }
            }
        }
        return $templateIds;
    }

    /**
     * @param \Magento\Email\Model\ResourceModel\Template\Collection $templateCollection
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getSandboxTemplateIds(
        \Magento\Email\Model\ResourceModel\Template\Collection $templateCollection
    ) : array {
        $templateIds = [];
        foreach ($templateCollection as $template) {
            if ($this->ampTemplateProvider->isExistAmpForEmailById($template->getId())) {
                $ampTemplate = $this->ampTemplateProvider->getTemplate($template->getId());
                if ($ampTemplate->isEnabledAmpForEmail() && $ampTemplate->isAmpForEmailInSandbox()) {
                    $templateIds[] = $template->getId();
                }
            }
        }
        return $templateIds;
    }

    /**
     * @param \Magento\Email\Model\ResourceModel\Template\Collection $templateCollection
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getDisabledTemplateIds(
        \Magento\Email\Model\ResourceModel\Template\Collection $templateCollection
    ) : array {
        $templateIds = [];

        foreach ($templateCollection as $template) {
            if ($this->ampTemplateProvider->isExistAmpForEmailById($template->getId())) {
                $ampTemplate = $this->ampTemplateProvider->getTemplate($template->getId());
                if (! $ampTemplate->isEnabledAmpForEmail()) {
                    $templateIds[] = $template->getId();
                }
            } else {
                $templateIds[] = $template->getId();
            }
        }
        return $templateIds;
    }
}
