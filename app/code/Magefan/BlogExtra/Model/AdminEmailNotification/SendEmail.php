<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogExtra\Model\AdminEmailNotification;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Magefan\Blog\Model\PostRepository;
use Magento\Framework\Translate\Inline\StateInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\App\Emulation;

/**
 * Class SendEmail
 */
class SendEmail extends AbstractModel
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var PostRepository
     */
    protected $postRepository;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Emulation
     */
    protected $emulation;

    /**
     * SendEmail constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param PostRepository $postRepository
     * @param StateInterface $inlineTranslation
     * @param LoggerInterface $logger
     * @param Emulation $emulation
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        PostRepository $postRepository,
        StateInterface $inlineTranslation,
        LoggerInterface $logger,
        Emulation $emulation
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->postRepository = $postRepository;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
        $this->emulation = $emulation;
    }

    /**
     * @param AbstractModel $subject
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendEmail(AbstractModel $subject)
    {

        if ($this->getConfigValue('mfblog/post_view/comments/admin_enable_comment_notification')) {
            try {

                // starting the store emulation with area defined for frontend
                $this->emulation->startEnvironmentEmulation($subject->getStoreId(), 'frontend');

                $this->inlineTranslation->suspend();

                $post = $this->postRepository->getById($subject->getData('post_id'));

                $template = $this->getConfigValue('mfblog/post_view/comments/admin_email_template');
                $recipientsAddresses = explode(',', $this->getConfigValue('mfblog/post_view/comments/admin_send_to'));

                foreach ($recipientsAddresses as $key => $address) {
                    $address = trim($address);
                    if (!$address) {
                        unset($recipientsAddresses[$key]);
                    } else {
                        $recipientsAddresses[$key] = $address;
                    }
                }

                if ($recipientsAddresses) {
                    $senderIdentity = $this->getConfigValue('mfblog/post_view/comments/admin_send_from');
                    $sender = [
                        'name' => $this->getConfigValue('trans_email/ident_' . $senderIdentity . '/name'),
                        'email' => $this->getConfigValue('trans_email/ident_' . $senderIdentity . '/email')
                    ];

                    $transportBuilder = $this->transportBuilder
                        ->setTemplateIdentifier($template)
                        ->setTemplateOptions([
                            'area' => Area::AREA_FRONTEND,
                            'store' => Store::DEFAULT_STORE_ID
                        ])
                        ->setTemplateVars([
                            'post' => $post,
                            'comment' => $subject
                        ])
                        ->setFrom($sender);

                    foreach ($recipientsAddresses as $recipientsAddresse) {
                        $transportBuilder->addTo($recipientsAddresse);
                    }

                    $transport = $transportBuilder->getTransport();
                    $transport->sendMessage();

                }
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        // discard the emulated environment
        $this->emulation->stopEnvironmentEmulation();
    }

    /**
     * @param $path
     * @return mixed
     */
    private function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}
