<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class Settings
 * @package Mageside\Recipe\Helper
 */
class Settings extends AbstractHelper
{
    /**
     * @var array
     */
    private $joinFields;

    /**
     * @var array
     */
    private $searchKeywords;

    /**
     * @var array
     */
    private $storeDefinedFields;

    /**
     * @var array
     */
    private $searchEngineFields;

    /**
     * Settings constructor.
     * @param Context $context
     * @param array $joinFields
     * @param array $searchKeywords
     * @param array $storeDefinedFields
     * @param array $searchEngineFields
     */
    public function __construct(
        Context $context,
        array $joinFields = [],
        array $searchKeywords = [],
        array $storeDefinedFields = [],
        array $searchEngineFields = []
    ) {
        $this->joinFields = $joinFields;
        $this->searchKeywords = $searchKeywords;
        $this->storeDefinedFields = $storeDefinedFields;
        $this->searchEngineFields = $searchEngineFields;
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getJoinFields()
    {
        return $this->joinFields;
    }

    /**
     * @return array
     */
    public function getSearchKeywords()
    {
        return $this->searchKeywords;
    }

    /**
     * @return array
     */
    public function getStoreDefinedFields()
    {
        return $this->storeDefinedFields;
    }

    /**
     * @return array
     */
    public function getStoreCookingFields()
    {
        return [
            'ingredients'   => ['measure', 'ingredient', 'url', 'actionDelete'],
            'method'        => ['step', 'actionDelete']
        ];
    }

    /**
     * @return array
     */
    public function getSearchEngineFields()
    {
        return $this->searchEngineFields;
    }

    /**
     * @return array
     */
    public function getStoreFilterOptionsFields()
    {
        return [
            'label'   => ['input', 'field'],
            'option_image'   => ['fileUploader', 'field'],
        ];
    }
}
