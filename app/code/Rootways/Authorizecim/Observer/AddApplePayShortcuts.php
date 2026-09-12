<?php
namespace Rootways\Authorizecim\Observer;

use Rootways\Authorizecim\Block\ApplePay\Shortcut\Button;
use Magento\Catalog\Block\ShortcutButtons;
use Magento\Checkout\Block\QuoteShortcutButtons;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class AddApplePayShortcuts implements ObserverInterface
{
    /**
     * Add Apple Pay shortcut button
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        // Remove button from catalog pages
        if ($observer->getData('is_catalog_product')) {
            return;
        }

        /** @var ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();
        $shortcut = $shortcutButtons->getLayout()->createBlock(Button::class);
        $shortcut->setIsCart(get_class($shortcutButtons) === QuoteShortcutButtons::class);
        $shortcutButtons->addShortcut($shortcut);
    }
}
