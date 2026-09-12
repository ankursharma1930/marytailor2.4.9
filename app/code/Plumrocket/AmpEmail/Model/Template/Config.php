<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Template;

class Config implements \Magento\Framework\Mail\Template\ConfigInterface
{
    /**
     * @var \Magento\Email\Model\Template\Config\Data
     */
    private $dataStorage;

    /**
     * @var \Magento\Framework\View\FileSystem
     */
    private $viewFileSystem;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory|ReadFactory
     */
    private $readDirFactory;

    /**
     * @var \Magento\Framework\View\Design\Theme\ThemePackageList|ThemePackageList
     */
    private $themePackages;

    /**
     * Config constructor.
     *
     * @param Config\Data                                           $dataStorage
     * @param \Magento\Framework\View\FileSystem                    $viewFileSystem
     * @param \Magento\Framework\View\Design\Theme\ThemePackageList $themePackages
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory   $readDirFactory
     */
    public function __construct(
        \Plumrocket\AmpEmail\Model\Template\Config\Data $dataStorage,
        \Magento\Framework\View\FileSystem $viewFileSystem,
        \Magento\Framework\View\Design\Theme\ThemePackageList $themePackages,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readDirFactory
    ) {
        $this->dataStorage = $dataStorage;
        $this->viewFileSystem = $viewFileSystem;
        $this->themePackages = $themePackages;
        $this->readDirFactory = $readDirFactory;
    }

    /**
     * Return list of all email templates, both default module and theme-specific templates
     *
     * @return array[]
     */
    public function getAvailableTemplates() : array
    {
        $templates = [];
        foreach (array_keys($this->dataStorage->get()) as $templateId) {
            $templates[] = [
                'value' => $templateId,
                'label' => $this->getTemplateLabel($templateId),
                'group' => $this->getTemplateModule($templateId),
            ];
            $themeTemplates = $this->getThemeTemplates($templateId);
            $templates = array_merge($templates, $themeTemplates); //@codingStandardsIgnoreLine same as in magento method
        }
        return $templates;
    }

    /**
     * Find all theme-based email templates for a given template ID
     *
     * @param string $templateId
     *
     * @return array[]
     */
    public function getThemeTemplates(string $templateId) : array
    {
        $templates = [];

        $area = $this->getTemplateArea($templateId);
        $module = $this->getTemplateModule($templateId);
        $filename = $this->getInfo($templateId, 'file');

        foreach ($this->themePackages->getThemes() as $theme) {
            if ($theme->getArea() === $area) {
                $themeDir = $this->readDirFactory->create($theme->getPath());
                $file = "$module/email/$filename";
                if ($themeDir->isExist($file)) {
                    $templates[] = [
                        'value' => sprintf(
                            '%s/%s/%s',
                            $templateId,
                            $theme->getVendor(),
                            $theme->getName()
                        ),
                        'label' => sprintf(
                            '%s (%s/%s)',
                            $this->getTemplateLabel($templateId),
                            $theme->getVendor(),
                            $theme->getName()
                        ),
                        'group' => $this->getTemplateModule($templateId),
                    ];
                }
            }
        }

        return $templates;
    }

    /**
     * Parses a template ID and returns an array of templateId and theme
     *
     * @param string $templateId
     *
     * @return array an array of array('templateId' => '...', 'theme' => '...')
     */
    public function parseTemplateIdParts(string $templateId) : array
    {
        $parts = [
            'templateId' => $templateId,
            'theme' => null
        ];
        $pattern = '#^(?<templateId>[^/]+)/(?<themeVendor>[^/]+)/(?<themeName>[^/]+)#i';
        if (preg_match($pattern, $templateId, $matches)) {
            $parts['templateId'] = $matches['templateId'];
            $parts['theme'] = $matches['themeVendor'] . '/' . $matches['themeName'];
        }
        return $parts;
    }

    /**
     * Retrieve translated label of an email template
     *
     * @param string $templateId
     * @return \Magento\Framework\Phrase
     */
    public function getTemplateLabel($templateId) : \Magento\Framework\Phrase
    {
        return __($this->getInfo($templateId, 'label'));
    }

    /**
     * Retrieve type of an email template
     *
     * @param string $templateId
     * @return string
     */
    public function getTemplateType($templateId) : string
    {
        return $this->getInfo($templateId, 'type');
    }

    /**
     * Retrieve fully-qualified name of a module an email template belongs to
     *
     * @param string $templateId
     * @return string
     */
    public function getTemplateModule($templateId) : string
    {
        return $this->getInfo($templateId, 'module');
    }

    /**
     * Retrieve the area an email template belongs to
     *
     * @param string $templateId
     * @return string
     */
    public function getTemplateArea($templateId) : string
    {
        return $this->getInfo($templateId, 'area');
    }

    /**
     * Retrieve full path to an email template file
     *
     * @param string     $templateId
     * @param array|null $designParams
     *
     * @return string
     */
    public function getTemplateFilename($templateId, $designParams = []) : string
    {
        // If design params aren't passed, then use area/module defined in pramp_email_templates.xml
        if (! isset($designParams['area'])) {
            $designParams['area'] = $this->getTemplateArea($templateId);
        }
        $module = $this->getTemplateModule($templateId);
        $designParams['module'] = $module;

        $file = $this->getInfo($templateId, 'file');

        return $this->getFilename($file, $designParams, $module);
    }

    /**
     * Retrieve value of a field of an email template
     *
     * @param string $templateId Name of an email template
     * @param string $fieldName Name of a field value of which to return
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    public function getInfo(string $templateId, string $fieldName) : string
    {
        $data = $this->dataStorage->get();
        if (! isset($data[$templateId])) {
            throw new \UnexpectedValueException("Email template '{$templateId}' is not defined.");
        }
        if (! isset($data[$templateId][$fieldName])) {
            throw new \UnexpectedValueException(
                "Field '{$fieldName}' is not defined for email template '{$templateId}'."
            );
        }
        return $data[$templateId][$fieldName];
    }

    /**
     * Retrieve template file path.
     *
     * @param string $file
     * @param array $designParams
     * @param string $module
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    private function getFilename(string $file, array $designParams, string $module) : string
    {
        $filename = $this->viewFileSystem->getEmailTemplateFileName($file, $designParams, $module);

        if (false === $filename) {
            throw new \UnexpectedValueException("Template file '{$file}' is not found.");
        }

        return $filename;
    }
}
