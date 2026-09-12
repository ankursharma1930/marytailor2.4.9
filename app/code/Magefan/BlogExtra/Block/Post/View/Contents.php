<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogExtra\Block\Post\View;

use Magefan\Blog\Block\Post\View;
use Magefan\Blog\Model\ResourceModel\PageIdentifierGenerator;
use Magento\Store\Model\ScopeInterface;

/**
 * Blog sidebar post contents
 */
class Contents extends \Magefan\Blog\Block\Post\AbstractPost
{
    /**
     * @var PageIdentifierGenerator
     */
    private $pageIdentifierGenerator;

    /**
     * @return array
     */
    public function getItems(): array
    {
        $post = $this->getPost();
        if (!$post) {
            return [];
        }
        if (!$post->hasData('contents_items')) {
            $items = [];

            $result = $post->getFilteredContent();
            try {
                $previousErrorState = libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                $dom->loadHTML('<?xml encoding="UTF-8">' . '<body>' . $result . '</body>');
                libxml_use_internal_errors($previousErrorState);


                $body = $dom->getElementsByTagName('body');
                if ($body && $body->length > 0) {
                    foreach ($dom->getElementsByTagName('*') as $element) {
                        if (in_array($element->tagName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])
                            && false === strpos($element->getAttribute('class'), 'notoc')
                        ) {
                            if (!$element->getAttribute('id')) {
                                $object = new \Magento\Framework\DataObject([
                                    'title' => $element->textContent
                                ]);
                                $this->getPageIdentifierGenerator()->generate($object);
                                if ($object->getIdentifier()) {
                                    $element->setAttribute('id', $object->getIdentifier());
                                }
                            }
                            $level = (int)str_replace('h', '', $element->tagName);
                            $items[] = new \Magento\Framework\DataObject([
                                'level' => $level,
                                'title' => $element->textContent,
                                'url' => '#' . $element->getAttribute('id'),
                            ]);
                        }
                    }

                    $body = $body->item(0);
                    $_content = new \DOMDocument;
                    foreach ($body->childNodes as $child) {
                        $_content->appendChild($_content->importNode($child, true));
                    }
                    $result = $_content->saveHTML();
                    $post->setData('content_with_contents', $result);
                }
            } catch (\Exception $e) {
                $items = [];
            }
            $post->setData('contents_items', $items);
        }

        return $post->getData('contents_items') ?: [];
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getUpdatedContent(): string
    {
        if ($post = $this->getPost()) {
            $this->getItems();
            return $post->getData('content_with_contents') ?: '';
        }
        return '';
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toHtml()
    {
        if ($this->getItems()) {
            return parent::toHtml();
        }
        return '';
    }

    protected function getPageIdentifierGenerator()
    {
        if (null === $this->pageIdentifierGenerator) {
            $this->pageIdentifierGenerator = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(PageIdentifierGenerator::class);
        }

        return $this->pageIdentifierGenerator;
    }
}
