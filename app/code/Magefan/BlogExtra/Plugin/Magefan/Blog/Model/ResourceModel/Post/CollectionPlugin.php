<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogExtra\Plugin\Magefan\Blog\Model\ResourceModel\Post;

use Magefan\Blog\Model\ResourceModel\Post\Collection;
use Magento\Framework\Stdlib\DateTime\DateTime;

class CollectionPlugin
{
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @param DateTime $dateTime
     */
    public function __construct(
        DateTime $dateTime
    ) {
        $this->dateTime = $dateTime;
    }


    /**
     * @param Collection $subject
     * @param $result
     * @return Collection
     */
    public function afterAddActiveFilter(Collection $subject, $result) : Collection
    {
        $date = $this->dateTime->gmtDate();

        return  $result->addFieldToFilter(
            'end_time',
            [
                    ['gteq' => $date],
                    ['null' => true]
                ]
        );
    }
}
