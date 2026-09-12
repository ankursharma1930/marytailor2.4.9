<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\YouTubeWidget\Block\Widget;

use Magento\Store\Model\ScopeInterface;

/**
 * Widget YouTube Block
 */
class YouTube extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    /**
     * Default template file
     */
    const DEFAULT_TEMPLATE = 'Magefan_YouTubeWidget::default.phtml';

    /**
     * Default template file
     */
    const AMP_TEMPLATE = 'Magefan_YouTubeWidget::amp.phtml';

    /**
     * Default frame width
     */
    const DEFAULT_WIDTH = '100%';

    /**
     * Default frame height
     */
    const DEFAULT_HEIGHT = '500px';

    /**
     * Set template
     *
     * @return this
     */
    public function _toHtml()
    {
        $this->setTemplate(
            $this->isAmpEnabled() ? $this->getAmpTemplate() : $this->getDefaultTemplate()
        );
        return parent::_toHtml();
    }

    /**
     * Retrieve default .phtml template
     * @return string
     */
    public function getDefaultTemplate()
    {
        return self::DEFAULT_TEMPLATE;
    }

    /**
     * Retrieve amp .phtml template
     * @return string
     */
    public function getAmpTemplate()
    {
        return self::AMP_TEMPLATE;
    }

    /**
     * Retrieve true if amp is enabled
     * @return bool
     */
    protected function isAmpEnabled()
    {
        $isAmpRequest = $this->_scopeConfig->getValue(
            'pramp/general/enabled',
            ScopeInterface::SCOPE_STORE
        );

        if ($isAmpRequest) {
            /* We know that using objectManager is not a good practice,
            but if Plumrocket_AMP is not installed on your magento instance
            you'll get error during di:compile */
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $isAmpRequest = $objectManager->get('\Plumrocket\Amp\Helper\Data')
                ->isAmpRequest();
        }

        return $isAmpRequest;
    }

    /**
     * Retrieve embed url key
     * @return string
     */
    public function getVideoEmbedKey()
    {
        $k = 'embed_key';
        if (null === $this->getData($k)) {
            preg_match(
                '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',
                html_entity_decode($this->getData('video_url')),
                $match
            );
            $key = isset($match[1]) ? $match[1] : false;
            $this->setData($k, $key);
        }
        return $this->getData($k);
    }

    /**
     * Retrieve embed url
     * @return string
     */
    public function getVideoEmbedUrl()
    {
        $url = 'https://www.youtube.com/embed/'
            . $this->getVideoEmbedKey()
            . '?start=' . $this->getStartAt() . '&'
            . 'rel=' . ($this->getData('related_videos') ? 1 : 0) . '&'
            . 'showinfo=0&autoplay=1&modestbranding=1';

        $isLoop = (bool)$this->getData('loop');
        if ($isLoop) {
            $url .= '&loop=1&playlist=' . $this->getVideoEmbedKey();
        }

        return $url;
    }

    /**
     * Retrieve video preview url
     * @return string
     */
    public function getImageUrl()
    {
        $imageQuality = $this->getData('image_quality');
        return 'https://img.youtube.com/vi/'
            . $this->getVideoEmbedKey()
            . '/'
            . $imageQuality
            . '.jpg';
    }

    /**
     * Retrieve image background size
     * @return string
     */
    public function getImageSize()
    {
        return $this->getData('image_size') ? (string)$this->getData('image_size') : 'contain';
    }

    /**
     * Retrieve frame width
     * @return string
     */
    public function getWidth()
    {
        return $this->getData('width') ?: self::DEFAULT_WIDTH;
    }

    /**
     * Retrieve frane height
     * @return string
     */
    public function getHeigth()
    {
        return $this->getData('height') ?: self::DEFAULT_HEIGHT;
    }

    /**
     * Retrieve video start at
     * @return string
     */
    public function getStartAt()
    {
        $time = explode(':', (string)$this->getData('start_at'));

        if (1 == count($time)) {
            return trim($time[0]);
        }

        if (2 == count($time)) {
            return $time[0] * 60 + $time[1];
        }

        if (3 == count($time)) {
            return $time[0] * 3600 + $time[1] * 60 + $time[2];
        }
        return 0;
    }

    /**
     * Escape HTML entities
     *
     * @param string|array $data
     * @param array|null $allowedTags
     * @return string
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        $data = parent::escapeHtml($data, $allowedTags);
        return $this->escapeQuotes($data);
    }

    /**
     * Escape quotes
     *
     * @param string $data
     * @return string
     */
    public function escapeQuotes($data)
    {
        return str_replace(['"', '\''], '', $data);
    }

    /**
     * @return mixed
     */
    public function getVideoName()
    {
        return $this->getData('video_name');
    }

    /**
     * @return mixed
     */
    public function getVideoDescription()
    {
        return $this->getData('description');
    }

    /**
     * @return mixed
     */
    public function getUploadDate()
    {
        return $this->getData('upload_date');
    }

    /**
     * @return mixed
     */
    public function getContentUrl()
    {
        return $this->getData('video_url');
    }

    /**
     * @return string
     */
    public function getVideoDuration()
    {
        if ($this->getData('video_duration')) {
            $time = explode(':', (string)$this->getData('video_duration'));
            $duration = '';
            if (count($time) == 2) {
                $duration = 'T' . $time[0] . 'M' . $time[1] . 'S';
            } elseif (count($time) == 3) {
                $duration = 'T' . $time[0] . 'H' . $time[1] . 'M' . $time[2] . 'S';
            }
            return $duration;
        }
        return 'T0M0S';
    }

    /**
     * @return mixed
     */
    public function isSnippetEnabled()
    {
        return $this->getData('enable_snippet');
    }
}
