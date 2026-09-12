<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Template\Config;

use Magento\Framework\Component\ComponentRegistrar;

class FileResolver implements \Magento\Framework\Config\FileResolverInterface
{
    /**
     * @var \Magento\Framework\Config\FileIteratorFactory
     */
    private $iteratorFactory;

    /**
     * @var \Magento\Framework\Component\DirSearch
     */
    private $dirSearch;

    /**
     * FileResolver constructor.
     *
     * @param \Magento\Framework\Config\FileIteratorFactory $iteratorFactory
     * @param \Magento\Framework\Component\DirSearch        $dirSearch
     */
    public function __construct(
        \Magento\Framework\Config\FileIteratorFactory $iteratorFactory,
        \Magento\Framework\Component\DirSearch $dirSearch
    ) {
        $this->iteratorFactory = $iteratorFactory;
        $this->dirSearch = $dirSearch;
    }

    /**
     * {@inheritdoc}
     */
    public function get($filename, $scope)
    {
        $iterator = $this->iteratorFactory->create(
            $this->dirSearch->collectFiles(ComponentRegistrar::MODULE, 'etc/' . $filename)
        );
        return $iterator;
    }
}
