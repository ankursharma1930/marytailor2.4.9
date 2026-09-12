<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Component\Product;

use Plumrocket\AmpEmailApi\Block\ProductListComponentInterface;

/**
 * Class Bestsellers
 * Used for rendering amp list with bestsellers
 *
 * @method getPeriod()
 * @method getProductsCount()
 */
class Bestsellers extends \Plumrocket\AmpEmailApi\Block\AbstractProductComponent implements
    ProductListComponentInterface
{
    /**
     * @var string
     */
    protected $styleFileId = 'Plumrocket_AmpEmail::css/component/:version/product/amp-carousel.css';

    /**
     * @return string
     */
    public function getListUrl() : string
    {
        return $this->getAmpApiUrl(
            'amp-email-api/V1/product_bestseller',
            [
                'period' => $this->getPeriod(),
                'count' => $this->getProductsCount(),
            ]
        );
    }
}
