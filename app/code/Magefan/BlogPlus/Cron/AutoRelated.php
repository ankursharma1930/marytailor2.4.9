<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogPlus\Cron;

/**
 * Class AutoRelated
 */
class AutoRelated
{
    /**
     * @var \Magefan\BlogPlus\Model\AutoRelated\PostProcessor
     */
    protected $postProcessor;

    /**
     * @var \Magefan\BlogPlus\Model\AutoRelated\ProductProcessor
     */
    protected $productProcessor;

    /**
     * AutoRelated constructor.
     * @param \Magefan\BlogPlus\Model\AutoRelated\PostProcessor $postProcessor
     * @param \Magefan\BlogPlus\Model\AutoRelated\ProductProcessor $productProcessor
     */
    public function __construct(
        \Magefan\BlogPlus\Model\AutoRelated\PostProcessor $postProcessor,
        \Magefan\BlogPlus\Model\AutoRelated\ProductProcessor $productProcessor
    ) {
        $this->postProcessor = $postProcessor;
        $this->productProcessor = $productProcessor;
    }

    /**
     * Method which called in cron job
     */
    public function execute()
    {
        $this->postProcessor->execute();
        $this->productProcessor->execute();
    }
}
