<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Template\Config;

use Magento\Framework\Filesystem\DriverPool;

/**
 * Class FileIterator
 */
class FileIterator extends \Magento\Framework\Config\FileIterator
{
    /**
     * @var \Magento\Framework\Module\Dir\ReverseResolver
     */
    private $moduleDirResolver;

    /**
     * FileIterator constructor.
     *
     * @param \Magento\Framework\Filesystem\File\ReadFactory $readFactory
     * @param array                                          $paths
     * @param \Magento\Framework\Module\Dir\ReverseResolver  $dirResolver
     */
    public function __construct(
        \Magento\Framework\Filesystem\File\ReadFactory $readFactory,
        array $paths,
        \Magento\Framework\Module\Dir\ReverseResolver $dirResolver
    ) {
        parent::__construct($readFactory, $paths);
        $this->moduleDirResolver = $dirResolver;
    }

    /**
     * @return string
     * @throws \UnexpectedValueException
     */
    public function current()
    {
        $path = $this->key();
        $moduleName = $this->moduleDirResolver->getModuleName($path);
        if (!$moduleName) {
            throw new \UnexpectedValueException(
                sprintf("Unable to determine a module, file '%s' belongs to.", $this->key())
            );
        }

        $fileRead = $this->fileReadFactory->create($this->key(), DriverPool::FILE);
        $contents = $fileRead->readAll();
        return str_replace('<template ', '<template module="' . $moduleName . '" ', $contents);
    }
}
