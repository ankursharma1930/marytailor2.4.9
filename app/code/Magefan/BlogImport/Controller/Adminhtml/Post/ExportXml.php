<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogImport\Controller\Adminhtml\Post;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;

class ExportXml extends \Magefan\Blog\Controller\Adminhtml\Post
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    /**
     * Export rates grid to XML format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $filter = $this->getRequest()->getParam('filter');
        if ($filter) {
            $filter = base64_decode($filter);
            parse_str($filter, $filter);
            foreach ($filter as $k => $v) {
                if (is_array($v)) {
                    unset($filter[$k]);
                }
            }
            $filter = base64_encode(http_build_query($filter));
            $this->getRequest()->setParam('filter', $filter);
        }
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $content = $resultLayout->getLayout()->getChildBlock('blog.post.grid', 'grid.export');

        return $this->getFileFactory()->create(
            'blog_posts.xml',
            $content->getExcelFile(),
            DirectoryList::VAR_DIR
        );
    }

    /**
     * @return \Magento\Framework\App\Response\Http\FileFactory
     */
    private function getFileFactory()
    {
        if (null === $this->fileFactory) {
            $this->fileFactory = $this->_objectManager->get(\Magento\Framework\App\Response\Http\FileFactory::class);
        }
        return $this->fileFactory;
    }
}
