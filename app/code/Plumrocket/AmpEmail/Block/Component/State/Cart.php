<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Block\Component\State;

/**
 * Class Cart
 *
 * @method null|string getStateId()
 * @method null|string getIdPrefix()
 */
class Cart extends \Plumrocket\AmpEmailApi\Block\AbstractAmpBlock
{
    /**
     * @return string
     */
    public function getAddToCartUrl() : string
    {
        return $this->getAmpApiUrl('amp-email-api/V1/product_add');
    }
}
