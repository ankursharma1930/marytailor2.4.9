<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Model;

use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;

class FileUploader
{

    protected $fileSystem;

    /**
     * @var \Magento\Framework\Filesystem\Filesystem
     */
    protected $_fileSystem;
    /**
     * Base tmp path
     *
     * @var string
     */
    protected $baseTmpPath = 'recipe/tmp';
    /**
     * Base path
     *
     * @var string
     */
    protected $basePath = 'recipe';

    /**
     * @var array
     */
    protected $allowedExtensions = ['jpg', 'jpeg', 'gif', 'png'];

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $coreFileStorageDatabase;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * FileUploader constructor.
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_uploaderFactory = $uploaderFactory;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->_storeManager = $storeManager;
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->logger = $logger;
        $this->initFilesystem();
    }

    /**
     * @param $fileName
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function moveFileFromTmp($fileName)
    {
        $baseTmpPath = $this->getBaseTmpPath();
        $basePath = $this->getBasePath();

        $baseImagePath = $this->getFilePath($basePath, $fileName);
        $baseTmpImagePath = $this->getFilePath($baseTmpPath, $fileName);

        if (!$this->_mediaDirectory->isExist($baseTmpImagePath)) {
            return $fileName;
        }

        $fileNameNew = \Magento\MediaStorage\Model\File\Uploader::getNewFileName(
            $this->_mediaDirectory->getAbsolutePath($baseImagePath)
        );
        if ($fileName !== $fileNameNew) {
            $fileName = $fileNameNew;
            $baseImagePath = $this->getFilePath($basePath, $fileName);
        }

        try {
            $this->_mediaDirectory->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the file(s).')
            );
        }

        return $fileName;
    }

    public function saveFileToDir($fileId)
    {
        $baseTmpPath = $this->getBaseTmpPath();

        $uploader = $this->_uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions($this->getAllowedExtensions());
        $uploader->setAllowRenameFiles(true);

        $result = $uploader->save(
            $this->_mediaDirectory->getAbsolutePath($baseTmpPath)
        );

        if (!$result) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('File can not be saved to the destination folder.')
            );
        }

        $result['tmp_name'] = str_replace('\\', '/', $result['tmp_name']);
        $result['path'] = str_replace('\\', '/', $result['path']);
        $result['url'] = $this->getFileWebUrl($result['file'], 'temp');
        $result['name'] = $result['file'];

        return $result;
    }

    /**
     * @param $file
     * @param string $storeDir
     * @return string
     */
    public function getFileWebUrl($file, $storeDir = 'base')
    {
        switch ($storeDir) {
            case 'temp':
                $path = $this->getBaseTmpPath();
                break;
            case 'base':
            default:
                $path = $this->getBasePath();
                break;
        }

        return $this->_storeManager
                ->getStore()
                ->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $this->getFilePath($path, $file);
    }

    protected function initFilesystem()
    {
        if (!$this->_mediaDirectory->isExist($this->getBasePath())) {
            $this->_mediaDirectory->create($this->getBasePath());
        }

        if (!$this->_mediaDirectory->isExist($this->getBaseTmpPath())) {
            $this->_mediaDirectory->create($this->getBaseTmpPath());
        }
    }

    /**
     * Retrieve base tmp path
     *
     * @return string
     */
    public function getBaseTmpPath()
    {
        return $this->baseTmpPath;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    public function getBaseUrl()
    {
        $directLinkType = \Magento\Framework\UrlInterface::URL_TYPE_MEDIA;

        return $this->_storeManager->getStore()->getBaseUrl($directLinkType);
    }

    public function getFilePath($path, $imageName)
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }
}
