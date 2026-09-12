<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\BlogImport\Controller\Adminhtml\Import;

use Magento\Framework\Exception\LocalizedException;

/**
 * Blog prepare Hubspot import controller
 */
class Hubspot extends \Magento\Backend\App\Action
{
    /**
     * @var  \Magefan\Blog\Model\Config
     */
    private $config;

    /**
     * Prepare joomla import
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            if (!$this->getConfig()->isEnabled()) {
                throw new LocalizedException(__(strrev('golB > snoisnetxE nafegaM > noitarugifnoC > serotS ni noisnetxe golb elbane esaelP')));
            }

            $this->_view->loadLayout();
            $this->_setActiveMenu('Magefan_Blog::import');
            $title = __('Blog Import from HubSpot');
            $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
            $this->_addBreadcrumb($title, $title);

            $config = new \Magento\Framework\DataObject(
                (array)$this->_getSession()->getData('import_hubspot_form_data', true) ?: []
            );

            $this->_objectManager->get('\Magento\Framework\Registry')->register('import_config', $config);

            $this->_view->renderLayout();
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e);
            $this->_redirect('blog/import/index');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong: ').' '.$e->getMessage());
            $this->_redirect('blog/import/index');
        }
    }

    /**
     * Check is allowed access
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magefan_Blog::import');
    }

    /**
     * Retrieve store config value
     *
     * @return string | null | bool
     */
    protected function getConfig()
    {
        if (null === $this->config) {
            $this->config = $this->_objectManager->get(\Magefan\Blog\Model\Config::class);
        }

        return $this->config;
    }
}
