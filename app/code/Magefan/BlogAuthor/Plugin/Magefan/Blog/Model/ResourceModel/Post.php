<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogAuthor\Plugin\Magefan\Blog\Model\ResourceModel;

use Magefan\Blog\Model\ResourceModel\Post as PostResourceModel;

class Post
{
    /**
     * @param PostResourceModel $resourceModel
     * @param $result
     * @param $subject
     * @param $object
     * @return mixed
     */
    public function afterSave(PostResourceModel $resourceModel, $result, $object)
    {
        /* Save coouthors */
        foreach ([ 'coauthor' => 'coauthors'] as $linkType => $dataKey) {
            if (null !== $object->getData($dataKey)) {
                $newIds = (array)$object->getData($dataKey);
                foreach ($newIds as $key => $id) {
                    if (!$id) { // e.g.: zero
                        unset($newIds[$key]);
                    }
                }
                if (is_array($newIds)) {
                    $oldIds = $resourceModel->lookupIds($object->getId(), 'magefan_blog_post_coauthor', 'coauthor_id');

                    $resourceModel->updateLinks(
                        $object,
                        $newIds,
                        $oldIds,
                        'magefan_blog_post_' . $linkType,
                        $linkType . '_id'
                    );
                }
            }
        }

        return $result;
    }


    /**
     * @param PostResourceModel $resourceModel
     * @param $result
     * @param $subject
     * @param $object
     * @return mixed
     */
    public function afterLoad(PostResourceModel $resourceModel, $result, $object)
    {
        if ($object->getId()) {
            $coauthorIds = $resourceModel->lookupIds($object->getId(), 'magefan_blog_post_coauthor', 'coauthor_id');
            $object->setData('coauthors', $coauthorIds);
        }
    }
}