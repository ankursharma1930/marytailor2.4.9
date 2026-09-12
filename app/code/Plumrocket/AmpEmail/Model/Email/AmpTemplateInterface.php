<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Email;

interface AmpTemplateInterface extends \Magento\Framework\Mail\TemplateInterface
{
    const AMP_EMAIL_STATUS_DISABLED = '0';
    const AMP_EMAIL_STATUS_LIVE = 'live';
    const AMP_EMAIL_STATUS_SANDBOX = 'sandbox';

    const TESTING_METHOD_AUTO = 'auto';
    const TESTING_METHOD_MANUAL = 'manual';

    /**
     * Check if all ready for render AMP
     *
     * @param \Plumrocket\AmpEmail\Model\Email\AmpMessageInterface|null $message
     * @return bool
     */
    public function canRenderAmpForEmail(\Plumrocket\AmpEmail\Model\Email\AmpMessageInterface $message = null) : bool;

    /**
     * Load template by self id
     *
     * @return \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface
     * @throws \UnexpectedValueException
     */
    public function loadTemplate() : AmpTemplateInterface;

    /**
     * Check if exist AMP content
     *
     * @return bool
     */
    public function isExistAmpForEmail() : bool;

    /**
     * Check if AMP enabled
     *
     * @return bool
     */
    public function isEnabledAmpForEmail() : bool;

    /**
     * @return bool
     */
    public function isAmpForEmailInSandbox() : bool;

    /**
     * @return bool
     */
    public function isAmpForEmailInLive() : bool;

    /**
     * @return string
     */
    public function getPrampEmailContent() : string;

    /**
     * @param string $content
     * @return \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface
     */
    public function setPrampEmailContent(string $content) : AmpTemplateInterface;

    /**
     * @return string
     */
    public function getPrampEmailStyles() : string;

    /**
     * @param string $styles
     * @return \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface
     */
    public function setPrampEmailStyles(string $styles) : AmpTemplateInterface;

    /**
     * @return string
     */
    public function getPrampEmailMode() : string;

    /**
     * @param $emailMode
     * @return \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface
     */
    public function setPrampEmailMode($emailMode) : AmpTemplateInterface;

    /**
     * @return string
     */
    public function getPrampEmailTestingMethod() : string;

    /**
     * @param $emailTestingMethod
     * @return \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface
     */
    public function setPrampEmailTestingMethod($emailTestingMethod) : AmpTemplateInterface;

    /**
     * @return array
     */
    public function getPrampEmailAutomaticEmails() : array;

    /**
     * @param string|array|null $emails
     * @return \Plumrocket\AmpEmail\Model\Email\AmpTemplateInterface
     */
    public function setPrampEmailAutomaticEmails($emails) : AmpTemplateInterface;
}
