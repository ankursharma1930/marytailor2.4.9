<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2023 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Template;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filter\Template\SignatureProvider;
use Plumrocket\AmpEmail\Model\Magento\VersionProvider;

/**
 * @since 2.2.0
 */
class AddSignature
{

    /**
     * @var \Plumrocket\AmpEmail\Model\Magento\VersionProvider
     */
    private $versionProvider;

    /**
     * @param \Plumrocket\AmpEmail\Model\Magento\VersionProvider $versionProvider
     */
    public function __construct(VersionProvider $versionProvider)
    {
        $this->versionProvider = $versionProvider;
    }

    /**
     * Sign directive to be processed in second cycle of \Magento\Framework\Filter\Template::filter
     *
     * @param string $value
     * @param string $directive
     * @return string
     */
    public function execute(string $value, string $directive): string
    {
        if ($this->versionProvider->isMagentoVersionBelow('2.4.6')) {
            return $value;
        }
        $sign = $this->getSignatureProvider()->get();
        $pattern = "/{{{$directive}.*?}}/si";
        if (preg_match_all($pattern, $value, $directives)) {
            foreach ($directives as $directiveContent) {
                $value = str_replace($directiveContent, "$sign{$directiveContent[0]}$sign", $value);
            }
        }
        return $value;
    }

    /**
     * Get signature Provider.
     *
     * @return \Magento\Framework\Filter\Template\SignatureProvider
     * @deprecated since 2.2.1 added only for compatibility
     * @see signatureProvider
     */
    public function getSignatureProvider()
    {
        return ObjectManager::getInstance()->get(
            \Magento\Framework\Filter\Template\SignatureProvider::class
        );
    }
}
