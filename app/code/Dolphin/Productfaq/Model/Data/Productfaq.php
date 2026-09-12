<?php


namespace Dolphin\Productfaq\Model\Data;

use Dolphin\Productfaq\Api\Data\ProductfaqInterface;
use Magento\Framework\Api\AbstractExtensibleObject;
use Dolphin\Productfaq\Api\Data\ProductfaqExtensionInterface;

class Productfaq extends AbstractExtensibleObject implements ProductfaqInterface
{

    /**
     * Get productfaq_id
     *
     * @return string|null
     */
    public function getProductfaqId()
    {
        return $this->_get(self::PRODUCTFAQ_ID);
    }

    /**
     * Set productfaq_id
     *
     * @param string $productfaqId
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setProductfaqId($productfaqId)
    {
        return $this->setData(self::PRODUCTFAQ_ID, $productfaqId);
    }

    /**
     * Get question
     *
     * @return string|null
     */
    public function getQuestion()
    {
        return $this->_get(self::QUESTION);
    }

    /**
     * Set question
     *
     * @param string $question
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setQuestion($question)
    {
        return $this->setData(self::QUESTION, $question);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return ProductfaqExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @param ProductfaqExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        ProductfaqExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get answer
     *
     * @return string|null
     */
    public function getAnswer()
    {
        return $this->_get(self::ANSWER);
    }

    /**
     * Set answer
     *
     * @param string $answer
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setAnswer($answer)
    {
        return $this->setData(self::ANSWER, $answer);
    }

    /**
     * Get store
     *
     * @return string|null
     */
    public function getStore()
    {
        return $this->_get(self::STORE);
    }

    /**
     * Set store
     *
     * @param string $store
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setStore($store)
    {
        return $this->setData(self::STORE, $store);
    }

    /**
     * Get email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->_get(self::EMAIL);
    }

    /**
     * Set email
     *
     * @param string $email
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Get group
     *
     * @return string|null
     */
    public function getGroup()
    {
        return $this->_get(self::GROUP);
    }

    /**
     * Set group
     *
     * @param string $group
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setGroup($group)
    {
        return $this->setData(self::GROUP, $group);
    }

    /**
     * Get visibility
     *
     * @return string|null
     */
    public function getVisibility()
    {
        return $this->_get(self::VISIBILITY);
    }

    /**
     * Set visibility
     *
     * @param string $visibility
     * @return \Dolphin\Productfaq\Api\Data\ProductfaqInterface
     */
    public function setVisibility($visibility)
    {
        return $this->setData(self::VISIBILITY, $visibility);
    }
}
