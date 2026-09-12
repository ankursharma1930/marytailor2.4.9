<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_AmpEmailApi
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmailApi\Block;

abstract class AbstractComponent extends \Plumrocket\AmpEmailApi\Block\AbstractAmpBlock implements
    \Plumrocket\AmpEmailApi\Api\ComponentInterface
{
    use \Plumrocket\AmpEmailApi\Block\ComponentTrait;

    /**
     * Path to component css file
     *
     * @var string
     */
    protected $styleFileId = '';

    /**
     * List of require states
     *
     * @var array
     */
    protected $ampStates = [];

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $viewAssetRepository;

    /**
     * AbstractComponent constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context          $context
     * @param \Magento\Framework\Url                                    $frontUrlBuilder
     * @param \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator
     * @param \Magento\Framework\View\Asset\Repository                  $viewAssetRepository
     * @param array                                                     $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url $frontUrlBuilder,
        \Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface $componentDataLocator,
        \Magento\Framework\View\Asset\Repository $viewAssetRepository,
        array $data = []
    ) {
        parent::__construct($context, $frontUrlBuilder, $componentDataLocator, $data);
        $this->viewAssetRepository = $viewAssetRepository;
    }

    /**
     * Retrieve Path to component css file
     *
     * @return string
     */
    public function getStyleFileId() : string
    {
        return str_replace(':version', 'v' . $this->getVersion(), $this->styleFileId);
    }

    /**
     * Retrieve Path to component css file
     *
     * @return string
     */
    public function getTemplate() : string
    {
        return str_replace(':version', 'v' . $this->getVersion(), parent::getTemplate());
    }

    /**
     * Retrieve version of component
     *
     * @return int
     */
    public function getVersion() : int
    {
        return (int) $this->_getData('version');
    }

    /**
     * Setter for component version
     *
     * @param int $version
     * @return \Plumrocket\AmpEmailApi\Block\AbstractComponent
     */
    public function setVersion(int $version) : \Plumrocket\AmpEmailApi\Block\AbstractComponent
    {
        return $this->setData('version', $version);
    }

    /**
     * Add "css" and "state" parts to email template
     *
     * @param $html
     * @return string
     */
    protected function _afterToHtml($html)
    {
        if ($html) {
            if ($cssContent = $this->getStyleFileContent()) {
                $this->getComponentPartsCollector()->addPartToList(
                    'css',
                    $cssContent,
                    $this->getStyleFileId()
                );
            }

            foreach ($this->ampStates as $stateKey => $stateData) {
                $this->getComponentPartsCollector()->addPartToList(
                    'state',
                    $stateData,
                    $stateKey
                );
            }
        }

        return parent::_afterToHtml($html);
    }

    /**
     * Retrieve components css
     *
     * @api point for dynamically change style of component
     * @return bool|string
     */
    public function getStyleFileContent()
    {
        if (! $this->getStyleFileId()) {
            return '';
        }

        $asset = $this->getViewAssetRepository()->createAsset($this->getStyleFileId());
        return $asset->getContent();
    }

    /**
     * @return \Magento\Framework\View\Asset\Repository
     */
    public function getViewAssetRepository() : \Magento\Framework\View\Asset\Repository
    {
        return $this->viewAssetRepository;
    }
}
