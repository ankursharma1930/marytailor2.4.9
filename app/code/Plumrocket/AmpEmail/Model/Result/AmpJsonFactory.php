<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Result;

use Magento\Framework\ObjectManagerInterface;

class AmpJsonFactory implements \Plumrocket\AmpEmailApi\Model\Result\AmpJsonFactoryInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Plumrocket\AmpEmail\Helper\Cors
     */
    private $corsHelper;

    /**
     * ResultFactory constructor.
     *
     * @param ObjectManagerInterface      $objectManager
     * @param \Plumrocket\AmpEmail\Helper\Cors $corsHelper
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        \Plumrocket\AmpEmail\Helper\Cors $corsHelper
    ) {
        $this->objectManager = $objectManager;
        $this->corsHelper = $corsHelper;
    }

    /**
     * @param array $arguments
     * @return \Plumrocket\AmpEmailApi\Model\Result\AmpJsonInterface
     */
    public function create(array $arguments = []) : \Plumrocket\AmpEmailApi\Model\Result\AmpJsonInterface
    {
        /** @var AmpJson $result */
        $result = $this->objectManager->create(AmpJson::class, $arguments); //@codingStandardsIgnoreLine

        $this->corsHelper->prepareHeadersForAmpResponse($result);

        $result->setHeader('Cache-Control', 'max-age=0, private, no-cache, no-store');

        return $result;
    }
}
