<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function convert($source) //@codingStandardsIgnoreLine
    {
        $components = [];
        $xpath = new \DOMXPath($source); //@codingStandardsIgnoreLine
        /** @var $component \DOMNode */
        foreach ($xpath->query('/components/component') as $component) { //@codingStandardsIgnoreLine
            $componentAttributes = $component->attributes;
            $componentArray = ['@' => []];
            $componentArray['@']['type'] = $componentAttributes->getNamedItem('class')->nodeValue;

            $placeholderImage = $componentAttributes->getNamedItem('placeholder_image');
            if ($placeholderImage !== null) {
                $componentArray['placeholder_image'] = $placeholderImage->nodeValue;
            }

            $componentId = $componentAttributes->getNamedItem('id');
            /** @var $componentSubNode \DOMNode */
            foreach ($component->childNodes as $componentSubNode) {
                switch ($componentSubNode->nodeName) {
                    case 'label':
                        $componentArray['name'] = $componentSubNode->nodeValue;
                        break;
                    case 'description':
                        $componentArray['description'] = $componentSubNode->nodeValue;
                        break;
                    case 'parameters':
                        /** @var $parameter \DOMNode */
                        foreach ($componentSubNode->childNodes as $parameter) {
                            if ($parameter->nodeName === '#text' || $parameter->nodeName === '#comment') {
                                continue;
                            }
                            $subNodeAttributes = $parameter->attributes;
                            $parameterName = $subNodeAttributes->getNamedItem('name')->nodeValue;
                            $componentArray['parameters'][$parameterName] = $this->convertParameter($parameter);
                        }
                        break;
                    case 'containers':
                        if (!isset($componentArray['supported_containers'])) {
                            $componentArray['supported_containers'] = [];
                        }
                        foreach ($componentSubNode->childNodes as $container) {
                            if ($container->nodeName === '#text' || $container->nodeName === '#comment') {
                                continue;
                            }
                            $componentArray['supported_containers'] = array_merge( //@codingStandardsIgnoreLine
                                $componentArray['supported_containers'],
                                $this->convertContainer($container)
                            );
                        }
                        break;
                    case '#text':
                        break;
                    case '#comment':
                        break;
                    default:
                        throw new \LogicException(
                            sprintf(
                                "Unsupported child xml node '%s' found in the 'component' node",
                                $componentSubNode->nodeName
                            )
                        );
                }
            }
            $components[$componentId->nodeValue] = $componentArray;
        }
        return $components;
    }

    /**
     * Convert dom Container node to Magento array
     *
     * @param \DOMNode $source
     * @return array
     * @throws \LogicException
     */
    private function convertContainer($source) : array
    {
        $supportedContainers = [];
        $containerAttributes = $source->attributes;
        $template = [];
        foreach ($source->childNodes as $containerTemplate) {
            if (!$containerTemplate instanceof \DOMElement) {
                continue;
            }
            if ($containerTemplate->nodeName !== 'template') {
                throw new \LogicException("Only 'template' node can be child of 'container' node");
            }
            $templateAttributes = $containerTemplate->attributes;
            $template[$templateAttributes->getNamedItem(
                'name'
            )->nodeValue] = $templateAttributes->getNamedItem(
                'value'
            )->nodeValue;
        }
        $supportedContainers[] = [
            'container_name' => $containerAttributes->getNamedItem('name')->nodeValue,
            'template' => $template,
        ];
        return $supportedContainers;
    }

    /**
     * Convert dom Parameter node to Magento array
     *
     * @param \DOMNode $source
     * @return array
     * @throws \LogicException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function convertParameter($source) : array //@codingStandardsIgnoreLine
    {
        $parameter = [];
        $sourceAttributes = $source->attributes;
        $xsiType = $sourceAttributes->getNamedItem('type')->nodeValue;
        if ($xsiType === 'block') {
            $parameter['type'] = 'label';
            $parameter['@'] = [];
            $parameter['@']['type'] = 'complex';
            foreach ($source->childNodes as $blockSubNode) {
                if ($blockSubNode->nodeName === 'block') {
                    $parameter['helper_block'] = $this->convertBlock($blockSubNode);
                    break;
                }
            }
        } elseif ($xsiType === 'select' || $xsiType === 'multiselect') {
            $sourceModel = $sourceAttributes->getNamedItem('source_model');
            if ($sourceModel !== null) {
                $parameter['source_model'] = $sourceModel->nodeValue;
            }
            $parameter['type'] = $xsiType;

            /** @var $paramSubNode \DOMNode */
            foreach ($source->childNodes as $paramSubNode) {
                if ($paramSubNode->nodeName === 'options') {
                    /** @var $option \DOMNode */
                    foreach ($paramSubNode->childNodes as $option) {
                        if ($option->nodeName === '#text') {
                            continue;
                        }
                        $optionAttributes = $option->attributes;
                        $optionName = $optionAttributes->getNamedItem('name')->nodeValue;
                        $selected = $optionAttributes->getNamedItem('selected');
                        if ($selected !== null) {
                            $parameter['value'] = $optionAttributes->getNamedItem('value')->nodeValue;
                        }
                        if (!isset($parameter['values'])) {
                            $parameter['values'] = [];
                        }
                        $parameter['values'][$optionName] = $this->convertOption($option);
                    }
                }
            }
        } elseif ($xsiType === 'text') {
            $parameter['type'] = $xsiType;
            foreach ($source->childNodes as $textSubNode) {
                if ($textSubNode->nodeName === 'value') {
                    $parameter['value'] = $textSubNode->nodeValue;
                }
            }
        } elseif ($xsiType === 'conditions') {
            $parameter['type'] = $sourceAttributes->getNamedItem('class')->nodeValue;
        } else {
            $parameter['type'] = $xsiType;
        }
        $visible = $sourceAttributes->getNamedItem('visible');
        if ($visible) {
            $parameter['visible'] = $visible->nodeValue === 'true' ? '1' : '0';
        } else {
            $parameter['visible'] = true;
        }
        $required = $sourceAttributes->getNamedItem('required');
        if ($required) {
            $parameter['required'] = $required->nodeValue === 'false' ? '0' : '1';
        }
        $sortOrder = $sourceAttributes->getNamedItem('sort_order');
        if ($sortOrder) {
            $parameter['sort_order'] = $sortOrder->nodeValue;
        }
        foreach ($source->childNodes as $paramSubNode) {
            switch ($paramSubNode->nodeName) {
                case 'label':
                    $parameter['label'] = $paramSubNode->nodeValue;
                    break;
                case 'description':
                    $parameter['description'] = $paramSubNode->nodeValue;
                    break;
                case 'depends':
                    $parameter['depends'] = $this->convertDepends($paramSubNode);
                    break;
            }
        }
        return $parameter;
    }

    /**
     * Convert dom Depends node to Magento array
     *
     * @param \DOMNode $source
     * @return array
     * @throws \LogicException
     */
    private function convertDepends($source) : array
    {
        $depends = [];
        foreach ($source->childNodes as $childNode) {
            if ($childNode->nodeName === '#text') {
                continue;
            }
            if ($childNode->nodeName !== 'parameter') {
                throw new \LogicException(
                    sprintf("Only 'parameter' node can be child of 'depends' node, %s found", $childNode->nodeName)
                );
            }
            $parameterAttributes = $childNode->attributes;
            $dependencyName = $parameterAttributes->getNamedItem('name')->nodeValue;
            $dependencyValue = $parameterAttributes->getNamedItem('value')->nodeValue;

            if (!isset($depends[$dependencyName])) {
                $depends[$dependencyName] = [
                    'value' => $dependencyValue,
                ];

                continue;
            }

            if (!isset($depends[$dependencyName]['values'])) {
                $depends[$dependencyName]['values'] = [$depends[$dependencyName]['value']];
                unset($depends[$dependencyName]['value']);
            }

            $depends[$dependencyName]['values'][] = $dependencyValue;
        }

        return $depends;
    }

    /**
     * Convert dom Renderer node to Magento array
     *
     * @param \DOMNode $source
     * @return array
     * @throws \LogicException
     */
    private function convertBlock($source) : array
    {
        $helperBlock = [];
        $helperBlock['type'] = $source->attributes->getNamedItem('class')->nodeValue;
        foreach ($source->childNodes as $blockSubNode) {
            if ($blockSubNode->nodeName === '#text') {
                continue;
            }
            if ($blockSubNode->nodeName !== 'data') {
                throw new \LogicException(
                    sprintf("Only 'data' node can be child of 'block' node, %s found", $blockSubNode->nodeName)
                );
            }
            $helperBlock['data'] = $this->convertData($blockSubNode);
        }
        return $helperBlock;
    }

    /**
     * Convert dom Data node to Magento array
     *
     * @param \DOMElement $source
     * @return array
     */
    private function convertData($source) : array
    {
        $data = [];
        if (!$source->hasChildNodes()) {
            return $data;
        }
        foreach ($source->childNodes as $dataChild) {
            if ($dataChild instanceof \DOMElement) {
                $data[$dataChild->attributes->getNamedItem('name')->nodeValue] = $this->convertData($dataChild);
            } elseif ('' === trim($dataChild->nodeValue)) {
                $data = $dataChild->nodeValue;
            }
        }
        return $data;
    }

    /**
     * Convert dom Option node to Magento array
     *
     * @param \DOMNode $source
     * @return array
     * @throws \LogicException
     */
    private function convertOption($source) : array
    {
        $option = [];
        $optionAttributes = $source->attributes;
        $option['value'] = $optionAttributes->getNamedItem('value')->nodeValue;
        foreach ($source->childNodes as $childNode) {
            if ($childNode->nodeName === '#text') {
                continue;
            }
            if ($childNode->nodeName !== 'label') {
                throw new \LogicException("Only 'label' node can be child of 'option' node");
            }
            $option['label'] = $childNode->nodeValue;
        }
        return $option;
    }
}
