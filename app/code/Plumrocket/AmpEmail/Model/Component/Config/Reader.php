<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Component\Config;

class Reader extends \Magento\Framework\Config\Reader\Filesystem
{
    /**
     * List of identifier attributes for merging
     *
     * @var array
     */
    protected $_idAttributes = [ //@codingStandardsIgnoreLine
        '/components/component' => 'id',
        '/components/component/parameters/parameter' => 'name',
        '/components/component/parameters/parameter/options/option' => 'name',
        '/components/component/containers/container' => 'name',
        '/components/component/containers/container/template' => 'name',
    ];

    /**
     * Reader constructor.
     *
     * @param \Magento\Framework\Config\FileResolverInterface       $fileResolver
     * @param \Plumrocket\AmpEmail\Model\Component\Config\Converter $converter
     * @param \Magento\Framework\Config\SchemaLocatorInterface      $schemaLocator
     * @param \Magento\Framework\Config\ValidationStateInterface    $validationState
     * @param string                                                $fileName
     * @param array                                                 $idAttributes
     * @param string                                                $domDocumentClass
     * @param string                                                $defaultScope
     */
    public function __construct( //@codingStandardsIgnoreLine
        \Magento\Framework\Config\FileResolverInterface $fileResolver,
        Converter $converter,
        \Magento\Framework\Config\SchemaLocatorInterface $schemaLocator,
        \Magento\Framework\Config\ValidationStateInterface $validationState,
        $fileName = 'pramp_email_component.xml',
        $idAttributes = [],
        $domDocumentClass = \Magento\Framework\Config\Dom::class,
        $defaultScope = 'global'
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }

    /**
     * Load configuration file
     *
     * @param string $file
     * @return array
     */
    public function readFile($file)
    {
        return $this->_readFiles([$file]);
    }
}
