<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Block\Html;

class Pager extends \Magento\Theme\Block\Html\Pager
{
    /**
     * Set _use_rewrite param to false
     *
     * @param array $params
     * @return string
     */
    public function getPagerUrl($params = [])
    {
        $urlParams = [
            '_current' => true,
            '_escape' => true,
            '_use_rewrite' => false,
            '_fragment' => $this->getFragment(),
            '_query' => $params
        ];

        return $this->getUrl($this->getPath(), $urlParams);
    }
}
