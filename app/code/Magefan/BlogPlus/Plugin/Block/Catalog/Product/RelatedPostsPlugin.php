<?php

/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogPlus\Plugin\Block\Catalog\Product;

use Magefan\Blog\Block\Catalog\Product\RelatedPosts;

/**
 * Class RelatedPostsPlugin
 */
class RelatedPostsPlugin
{
    /**
     * @param RelatedPosts $subject
     * @param $result
     * @return mixed
     */
    public function afterGetPostCollection(RelatedPosts $subject, $result)
    {
        if (!$result->isLoaded()) {
            $result->getSelect()->columns([
                'display_on_product' => 'rl.display_on_product'
            ])->where('display_on_product = 0 OR display_on_product IS NULL');
        }

        return $result;
    }
}
