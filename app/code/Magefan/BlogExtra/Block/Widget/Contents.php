<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogExtra\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class Contents extends Template implements BlockInterface
{
    /**
     * @var string
     */
    const BLOG_POST_CONTENTS = '<!-- BLOG_POST_CONTENTS -->';

    /**
     * @return string
     */
    public function toHtml()
    {
        return self::BLOG_POST_CONTENTS;
    }
}
