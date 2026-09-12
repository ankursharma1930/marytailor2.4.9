<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogPlus\Plugin\Magento\Catalog\Model\ResourceModel;

use Magefan\BlogPlus\Model\ResourceModel\ProductRelatedPost;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\App\RequestInterface;

class ProductPlugin
{
    /**
     * @var ProductRelatedPost
     */
    private $productRelatedPost;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * ProductPlugin constructor.
     * @param ProductRelatedPost $productRelatedPost
     * @param RequestInterface $request
     */
    public function __construct(
        ProductRelatedPost $productRelatedPost,
        RequestInterface $request
    ) {
        $this->productRelatedPost = $productRelatedPost;
        $this->request = $request;
    }

    /**
     * @param Product $subject
     * @param $result
     * @param $object
     * @return mixed
     */
    public function afterSave(Product $subject, $result, $object)
    {
        if ($object->getId()) {
            $data = $this->request->getParams();
            $links = isset($data['links']) ? $data['links'] : ['blog_related' => []];

            if (is_array($links)) {
                $linkType = 'blog_related';
                if (isset($links[$linkType]) && is_array($links[$linkType])) {
                    $linksData = [];
                    $keys = [
                        'position',
                        'display_on_product',
                        'display_on_post',
                        'auto_related',
                        'related_by_rule'
                    ];

                    foreach ($links[$linkType] as $item) {
                        $linksData[$item['id']] = [];
                        foreach ($keys as $key) {
                            $linksData[$item['id']][$key] = isset($item[$key]) ? $item[$key] : 0;
                        }
                    }
                    $links[$linkType] = $linksData;
                } else {
                    $links[$linkType] = [];
                }

                /* Save related post & product links */
                $linksData = $links[$linkType];

                $oldIds = $this->productRelatedPost->lookupRelatedPostIds($object->getId());
                $this->productRelatedPost->updateLinks(
                    $object,
                    array_keys($linksData),
                    $oldIds,
                    'magefan_blog_post_relatedproduct',
                    'post_id',
                    $linksData
                );
            }
        }

        return $result;
    }
}
