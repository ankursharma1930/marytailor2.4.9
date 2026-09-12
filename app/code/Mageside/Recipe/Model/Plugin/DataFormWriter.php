<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\Recipe\Model\Plugin;

use Mageside\Recipe\Model\ResourceModel\Writer;
use Mageside\Recipe\Model\FileUploader;

/**
 * Class WriterSave
 * @package Mageside\Recipe\Model\Plugin
 */
class DataFormWriter
{
    protected $availableImages = ['avatar', 'writer_image'];

    /**
     * @var FileUploader
     */
    protected $fileUploader;

    /**
     * @var Writer
     */
    protected $writerResourceModel;

    /**
     * DataFormWriter constructor.
     * @param Writer $writerResourceModel
     * @param FileUploader $fileUploader
     */
    public function __construct(
        Writer $writerResourceModel,
        FileUploader $fileUploader
    ) {
        $this->writerResourceModel = $writerResourceModel;
        $this->fileUploader = $fileUploader;
    }

    /**
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetItems(\Magento\Customer\Model\ResourceModel\Customer\Collection $subject, $result)
    {
        $ids = $subject->getAllIds();
        $writersData = $this->writerResourceModel->getWritersDataByIds($ids);
        if (!empty($writersData)) {
            foreach ($result as $item) {
                if (!empty($writersData[$item->getId()])) {
                    $writerData = $writersData[$item->getId()];
                    foreach ($this->availableImages as $imageName) {
                        if (!empty($writerData[$imageName])) {
                            $writerData[$imageName] = [
                                [
                                    'name' => $writerData[$imageName],
                                    'url' => $this->fileUploader->getFileWebUrl($writerData[$imageName])
                                ]
                            ];
                        }
                    }
                    $item->addData(['writer' => $writerData]);
                }
            }
        }

        return $result;
    }
}
