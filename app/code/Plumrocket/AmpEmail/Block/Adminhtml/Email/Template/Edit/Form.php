<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Adminhtml\Email\Template\Edit;

use Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface;

class Form extends \Magento\Email\Block\Adminhtml\Template\Edit\Form
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \Plumrocket\AmpEmail\Model\Template\Config
     */
    private $templateConfig;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context       $context
     * @param \Magento\Framework\Registry                   $registry
     * @param \Magento\Framework\Data\FormFactory           $formFactory
     * @param \Magento\Variable\Model\VariableFactory       $variableFactory
     * @param \Magento\Variable\Model\Source\Variables      $variables
     * @param \Magento\Framework\Serialize\Serializer\Json  $serializer
     * @param \Plumrocket\AmpEmail\Model\Template\Config    $templateConfig
     * @param array                                         $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Variable\Model\VariableFactory $variableFactory,
        \Magento\Variable\Model\Source\Variables $variables,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Plumrocket\AmpEmail\Model\Template\Config $templateConfig,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $variableFactory, $variables, $data, $serializer);

        $this->serializer = $serializer;
        $this->templateConfig = $templateConfig;
    }

    /**
     * Add fields to form and create template info form
     *
     * @return \Magento\Backend\Block\Widget\Form
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm() //@codingStandardsIgnoreLine we need extend this method
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Template Information'), 'class' => 'fieldset-wide']
        );

        $templateId = $this->getEmailTemplate()->getId();
        $fieldset->addField(
            'currently_used_for',
            'label',
            [
                'label' => __('Currently Used For'),
                'container_id' => 'currently_used_for',
                'after_element_html' => '<script>require(["prototype"], function () {' .
                    (!$this->getEmailTemplate()->getSystemConfigPathsWhereCurrentlyUsed() ? '$(\'' .
                        'currently_used_for' .
                        '\').hide(); ' : '') .
                    '});</script>'
            ]
        );

        $fieldset->addField(
            'template_code',
            'text',
            ['name' => 'template_code', 'label' => __('Template Name'), 'required' => true]
        );
        $fieldset->addField(
            'template_subject',
            'text',
            ['name' => 'template_subject', 'label' => __('Template Subject'), 'required' => true]
        );
        $fieldset->addField('orig_template_variables', 'hidden', ['name' => 'orig_template_variables']);
        $fieldset->addField(
            'variables',
            'hidden',
            ['name' => 'variables', 'value' => $this->serializer->serialize($this->getVariables())]
        );
        $fieldset->addField('template_variables', 'hidden', ['name' => 'template_variables']);

        $insertVariableButton = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class,
            '',
            [
                'data' => [
                    'type' => 'button',
                    'label' => __('Insert Variable...'),
                    'onclick' => 'templateControl.openVariableChooser();return false;',
                ]
            ]
        );

        $fieldset->addField('insert_variable', 'note', ['text' => $insertVariableButton->toHtml(), 'label' => '']);

        $fieldset->addField(
            'template_text',
            'textarea',
            [
                'name' => 'template_text',
                'label' => __('Template Content'),
                'title' => __('Template Content'),
                'required' => true,
                'style' => 'height:24em;'
            ]
        );

        if (!$this->getEmailTemplate()->isPlain()) {
            $fieldset->addField(
                'template_styles',
                'textarea',
                [
                    'name' => 'template_styles',
                    'label' => __('Template Styles'),
                    'container_id' => 'field_template_styles'
                ]
            );
        }

        $ampFieldset = $form->addFieldset(
            'pramp_email_fieldset',
            ['legend' => __('AMP ⚡ Email Template Information'), 'class' => 'fieldset-wide']
        );

        $prampEmailEnable = $ampFieldset->addField(
            'pramp_email_enable',
            'select',
            [
                'name' => 'pramp_email_enable',
                'label' => __('Enable ⚡ AMP'),
                'title' => __('Enable ⚡ AMP'),
                'options' => ['0' => __('No'), '1' => __('Yes')],
            ]
        );

        $prampEmailLoadTemplate = $ampFieldset->addField(
            'pramp_email_load_template',
            'select',
            [
                'name' => 'pramp_email_load_template',
                'label' => __('Load AMP Template'),
                'title' => __('Load AMP Template'),
                'options' => $this->getAmpTemplates(),
            ]
        );
        /** @var \Plumrocket\AmpEmail\Block\Adminhtml\Email\Template\Renderer\Load $loadRenderer */
        $loadRenderer = $this->getLayout()->createBlock(
            \Plumrocket\AmpEmail\Block\Adminhtml\Email\Template\Renderer\Load::class
        );
        $prampEmailLoadTemplate->setRenderer($loadRenderer);

        $prampEmailContent = $ampFieldset->addField(
            'pramp_email_content',
            'textarea',
            [
                'name' => 'pramp_email_content',
                'label' => __('AMP Template Content'),
                'title' => __('AMP Template Content'),
                'style' => 'height:24em;',
            ]
        );

        $prampEmailStyle = $ampFieldset->addField(
            'pramp_email_styles',
            'textarea',
            [
                'name' => 'pramp_email_styles',
                'label' => __('AMP Template Styles'),
                'title' => __('AMP Template Styles'),
            ]
        );

        $devFieldset = $form->addFieldset(
            'pramp_dev_fieldset',
            [
                'name' => 'pramp_dev_fieldset',
                'legend' => __('AMP ⚡ Email Testing'),
                'class' => 'fieldset-wide',
            ]
        );

        $prampEmailMode = $devFieldset->addField(
            'pramp_email_mode',
            'select',
            [
                'name' => 'pramp_email_mode',
                'label' => __('AMP Mode'),
                'title' => __('AMP Mode'),
                'options' => [
                    AmpTemplateInterface::AMP_EMAIL_STATUS_LIVE    => __('Live'),
                    AmpTemplateInterface::AMP_EMAIL_STATUS_SANDBOX => __('Sandbox')
                ],
                'note' => __(
                    'Select "Live" mode to enable AMP emails for all customers. ' .
                    '"Sandbox" mode should be used to test AMP emails.'
                ),
            ]
        );

        $prampEmailTestingMethod = $devFieldset->addField(
            'pramp_email_testing_method',
            'select',
            [
                'name' => 'pramp_email_testing_method',
                'label' => __('Testing Method'),
                'title' => __('Testing Method'),
                'options' => [
                    AmpTemplateInterface::TESTING_METHOD_MANUAL => __('Manual'),
                    AmpTemplateInterface::TESTING_METHOD_AUTO   => __('Automatic')
                ],
                'note' => __(
                    'When "Automatic" testing is enabled, only selected customers will be receiving this AMP email. ' .
                    'While "Manual" testing method allows to send test AMP Emails from this interface to any email ' .
                    'address.'
                ),
            ]
        );

        $prampEmailAutomaticEmails = $devFieldset->addField(
            'pramp_email_automatic_emails',
            'textarea',
            [
                'name'  => 'pramp_email_automatic_emails',
                'label' => __('Customer Emails'),
                'title' => __('Customer Emails'),
            ]
        );

        $prampEmailManualEmail = $devFieldset->addField(
            'pramp_email_manual_email',
            'text',
            ['name'  => 'pramp_email_manual_email']
        );
        /** @var \Plumrocket\AmpEmail\Block\Adminhtml\Email\Template\Renderer\CustomerEmail $emailManualSend */
        $emailManualSend = $this->getLayout()->createBlock(
            \Plumrocket\AmpEmail\Block\Adminhtml\Email\Template\Renderer\CustomerEmail::class
        );
        $emailManualSend->setElementParams(
            [
                'label' => __('Customer Email'),
                'title' => __('Customer Email'),
                'note'  => __(
                    'Enter Customer Account Email. The data from the selected Customer Account' .
                    ' will be used to autofill and test AMP Email template.'
                ),
            ]
        );
        $prampEmailManualEmail->setRenderer($emailManualSend);

        $prampEmailManualOrder = $devFieldset->addField(
            'pramp_email_manual_order',
            'select',
            [
                'name'  => 'pramp_email_manual_order',
                'label' => __('Customer Order'),
                'title' => __('Customer Order'),
                'note'  => __(
                    'Magento order of the selected customer. The data from this order will' .
                    ' be used to autofill and test AMP Email template.'
                ),
            ]
        );

        $prampEmailManualSend = $devFieldset->addField(
            'pramp_email_manual_send',
            'text',
            ['name'  => 'pramp_email_manual_send']
        );
        /** @var \Plumrocket\AmpEmail\Block\Adminhtml\Email\Template\Renderer\SentTestEmail $manualEmailRenderer */
        $emailManualSend = $this->getLayout()->createBlock(
            \Plumrocket\AmpEmail\Block\Adminhtml\Email\Template\Renderer\SentTestEmail::class
        );
        $emailManualSend->setElementParams(
            [
                'label' => __('Send Test Email'),
                'title' => __('Send Test Email'),
                'note'  => __(
                    'Recipient email address. If sending to multiple emails, separate them by commas.'
                ),
            ]
        );
        $prampEmailManualSend->setRenderer($emailManualSend);

        if ($templateId) {
            $form->addValues($this->getEmailTemplate()->getData());
        }

        $values = $this->_backendSession->getData('email_template_form_data', true);
        if ($values) {
            $form->setValues($values);
        }

        /** @var \Magento\Backend\Block\Widget\Form\Element\Dependence $dependencyBlock */
        $dependencyBlock = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Form\Element\Dependence::class
        );

        $dependencyBlock->addFieldMap(
            $prampEmailEnable->getHtmlId(),
            $prampEmailEnable->getName()
        )->addFieldMap(
            $prampEmailLoadTemplate->getHtmlId(),
            $prampEmailLoadTemplate->getName()
        )->addFieldMap(
            'pramp_email_load_template_button',
            'pramp_email_load_template_button'
        )->addFieldMap(
            $prampEmailContent->getHtmlId(),
            $prampEmailContent->getName()
        )->addFieldMap(
            $prampEmailStyle->getHtmlId(),
            $prampEmailStyle->getName()
        )->addFieldMap(
            $prampEmailMode->getHtmlId(),
            $prampEmailMode->getName()
        )->addFieldMap(
            $prampEmailTestingMethod->getHtmlId(),
            $prampEmailTestingMethod->getName()
        )->addFieldMap(
            $prampEmailAutomaticEmails->getHtmlId(),
            $prampEmailAutomaticEmails->getName()
        )->addFieldMap(
            $prampEmailManualEmail->getHtmlId(),
            $prampEmailManualEmail->getName()
        )->addFieldMap(
            $prampEmailManualOrder->getHtmlId(),
            $prampEmailManualOrder->getName()
        )->addFieldMap(
            $prampEmailManualSend->getHtmlId(),
            $prampEmailManualSend->getName()
        )->addFieldMap(
            $devFieldset->getHtmlId(),
            $devFieldset->getName()
        );

        $dependencyBlock->addFieldDependence(
            $prampEmailContent->getName(),
            $prampEmailEnable->getName(),
            '1'
        )->addFieldDependence(
            $prampEmailStyle->getName(),
            $prampEmailEnable->getName(),
            '1'
        )->addFieldDependence(
            $prampEmailLoadTemplate->getName(),
            $prampEmailEnable->getName(),
            '1'
        )->addFieldDependence(
            'pramp_email_load_template_button',
            $prampEmailEnable->getName(),
            '1'
        )->addFieldDependence(
            $devFieldset->getName(),
            $prampEmailEnable->getName(),
            '1'
        );

        $dependencyBlock->addFieldDependence(
            $prampEmailTestingMethod->getName(),
            $prampEmailMode->getName(),
            AmpTemplateInterface::AMP_EMAIL_STATUS_SANDBOX
        )->addFieldDependence(
            $prampEmailAutomaticEmails->getName(),
            $prampEmailMode->getName(),
            AmpTemplateInterface::AMP_EMAIL_STATUS_SANDBOX
        )->addFieldDependence(
            $prampEmailManualEmail->getName(),
            $prampEmailMode->getName(),
            AmpTemplateInterface::AMP_EMAIL_STATUS_SANDBOX
        )->addFieldDependence(
            $prampEmailManualOrder->getName(),
            $prampEmailMode->getName(),
            AmpTemplateInterface::AMP_EMAIL_STATUS_SANDBOX
        )->addFieldDependence(
            $prampEmailManualSend->getName(),
            $prampEmailMode->getName(),
            AmpTemplateInterface::AMP_EMAIL_STATUS_SANDBOX
        );

        $dependencyBlock->addFieldDependence(
            $prampEmailAutomaticEmails->getName(),
            $prampEmailTestingMethod->getName(),
            AmpTemplateInterface::TESTING_METHOD_AUTO
        );

        $dependencyBlock->addFieldDependence(
            $prampEmailManualEmail->getName(),
            $prampEmailTestingMethod->getName(),
            AmpTemplateInterface::TESTING_METHOD_MANUAL
        )->addFieldDependence(
            $prampEmailManualOrder->getName(),
            $prampEmailTestingMethod->getName(),
            AmpTemplateInterface::TESTING_METHOD_MANUAL
        )->addFieldDependence(
            $prampEmailManualSend->getName(),
            $prampEmailTestingMethod->getName(),
            AmpTemplateInterface::TESTING_METHOD_MANUAL
        );

        $this->setChild('form_after', $dependencyBlock);

        $this->setForm($form);

        return \Magento\Backend\Block\Widget\Form\Generic::_prepareForm();
    }

    /**
     * @return string[]
     */
    private function getAmpTemplates() : array
    {
        $options = array_merge(
            [['value' => '', 'label' => '', 'group' => '']],
            $this->templateConfig->getAvailableTemplates()
        );
        uasort(
            $options,
            static function (array $firstElement, array $secondElement) {
                return strcmp((string) $firstElement['label'], (string) $secondElement['label']);
            }
        );

        $groupedOptions = [];
        foreach ($options as $option) {
            $groupedOptions[$option['group']][] = $option;
        }
        ksort($groupedOptions);

        return $groupedOptions;
    }
}
