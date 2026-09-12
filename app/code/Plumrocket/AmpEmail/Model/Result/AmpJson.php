<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Result;

use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\Controller\Result\Json as MagentoJson;

class AmpJson extends MagentoJson implements \Plumrocket\AmpEmailApi\Model\Result\AmpJsonInterface
{
    const ERROR_KEY = 'error';
    const SUCCESS_KEY = 'success';

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $additionalData = [];

    /**
     * @var bool
     */
    private $isSingleListItem = false;

    /**
     * AmpJson constructor.
     *
     * @param \Magento\Framework\Translate\InlineInterface      $translateInline
     * @param \Psr\Log\LoggerInterface                          $logger
     */
    public function __construct(
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($translateInline);
        $this->logger = $logger;
    }

    /**
     * @param string|\Magento\Framework\Phrase $message
     * @return $this
     */
    public function addErrorMessage($message)
    {
        $this->setHttpResponseCode(400);
        $this->messages[self::ERROR_KEY][] = (string) $message;

        return $this;
    }

    /**
     * @param string|\Magento\Framework\Phrase $message
     * @return $this
     */
    public function addSuccessMessage($message)
    {
        $this->messages[self::SUCCESS_KEY][] = (string) $message;

        return $this;
    }

    /**
     * @param \Exception                       $exception
     * @param \Magento\Framework\Phrase|string $message
     * @return $this
     */
    public function addExceptionMessage(\Exception $exception, $message = null)
    {
        $this->logger->critical($exception);

        if (null === $message) {
            $message = $exception->getMessage();
        }

        $this->addErrorMessage((string) $message);

        return $this;
    }

    /**
     * @param string $key
     * @param        $value
     * @return $this
     */
    public function addData(string $key, $value)
    {
        $this->additionalData[$key] = $value;

        return $this;
    }

    /**
     * Format data as simple list item
     *
     * @param bool $flag
     * @return \Plumrocket\AmpEmail\Model\Result\AmpJson
     */
    public function setIsSingleListItem(bool $flag)
    {
        $this->isSingleListItem = $flag;
        return $this;
    }

    /**
     * @return array
     */
    private function getAdditionalData() : array
    {
        return $this->additionalData;
    }

    /**
     * @return array
     */
    private function getSerializedMessages() : array
    {
        $serialisedMessages = [];
        foreach ($this->messages as $type => $group) {
            $serialisedMessages[$type] = implode(' ', $group);
        }

        return $serialisedMessages;
    }

    /**
     * @param HttpResponseInterface $response
     * @return MagentoJson
     */
    protected function render(HttpResponseInterface $response) : MagentoJson //@codingStandardsIgnoreLine
    {
        if (null === $this->json) {
            $responseData = array_merge($this->getSerializedMessages(), $this->getAdditionalData());

            if ($this->isSingleListItem) {
                $responseData = ['items' => [$responseData]];
                $this->setIsSingleListItem(false);
            }

            $this->setData($responseData);
        }

        return parent::render($response);
    }
}
