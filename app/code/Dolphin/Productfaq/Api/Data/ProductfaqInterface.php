<?php

namespace Dolphin\Productfaq\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface ProductfaqInterface extends ExtensibleDataInterface
{
    public const EMAIL = 'email';
    public const QUESTION = 'question';
    public const PRODUCTFAQ_ID = 'productfaq_id';
    public const VISIBILITY = 'visibility';
    public const STORE = 'store';
    public const GROUP = 'group';
    public const ANSWER = 'answer';

    /**
     * Get productfaq_id
     *
     * @return string|null
     */
    public function getProductfaqId();

    /**
     * Set productfaq_id
     *
     * @param string $productfaqId
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setProductfaqId($productfaqId);

    /**
     * Get question
     *
     * @return string|null
     */
    public function getQuestion();

    /**
     * Set question
     *
     * @param string $question
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setQuestion($question);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Dolphin\Productfaq\Api\Data\ProductfaqExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Dolphin\Productfaq\Api\Data\ProductfaqExtensionInterface $extensionAttributes
    );

    /**
     * Get answer
     *
     * @return string|null
     */
    public function getAnswer();

    /**
     * Set answer
     *
     * @param string $answer
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setAnswer($answer);

    /**
     * Get store
     *
     * @return string|null
     */
    public function getStore();

    /**
     * Set store
     *
     * @param string $store
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setStore($store);

    /**
     * Get email
     *
     * @return string|null
     */
    public function getEmail();

    /**
     * Set email
     *
     * @param string $email
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setEmail($email);

    /**
     * Get group
     *
     * @return string|null
     */
    public function getGroup();

    /**
     * Set group
     *
     * @param string $group
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setGroup($group);

    /**
     * Get visibility
     *
     * @return string|null
     */
    public function getVisibility();

    /**
     * Set visibility
     *
     * @param string $visibility
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setVisibility($visibility);
}
