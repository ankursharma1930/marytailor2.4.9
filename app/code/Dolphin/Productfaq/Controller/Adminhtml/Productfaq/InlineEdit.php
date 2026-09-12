<?php

namespace Dolphin\Productfaq\Controller\Adminhtml\Productfaq;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Dolphin\Productfaq\Model\Productfaq;
use Magento\Framework\Controller\ResultInterface;

class InlineEdit extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Productfaq
     */
    protected $productfaqModel;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Productfaq $productfaqModel
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Productfaq $productfaqModel
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->productfaqModel = $productfaqModel;
    }

    /**
     * Inline edit action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];
        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $modelid) {

                    $model = $this->productfaqModel->load($modelid);
                    try {
                        //@codingStandardsIgnoreStart
                        $model->setData(array_merge($model->getData(), $postItems[$modelid]));
                        //@codingStandardsIgnoreEnd
                        $model->save();
                    } catch (\Exception $e) {
                        $messages[] = "[Productfaq ID: {$modelid}]  {$e->getMessage()}";
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error,
        ]);
    }
}
