<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Model\Shipping;

/**
 * Class CreateTrackUrl
 *
 * @api
 * @since 1.0.1
 */
class CreateTrackUrl
{
    /**
     * @param \Magento\Sales\Api\Data\TrackInterface $track
     * @return string
     */
    public function execute(\Magento\Sales\Api\Data\TrackInterface $track) : string
    {
        $trackingNumber = $track->getTrackNumber();

        switch ($track->getCarrierCode()) {
            case 'fedex':
                $url = "https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=$trackingNumber";
                break;
            case 'dhl':
                $url = "https://www.dhl.com/en/express/tracking.shtml?AWB=$trackingNumber&brand=DHL";
                break;
            case 'ups':
                $url = "https://www.ups.com/track?tracknum=$trackingNumber/";
                break;
            case 'usps':
                $url = "https://tools.usps.com/go/TrackConfirmAction?tLabels=$trackingNumber";
                break;
            default:
                $url = '';
        }

        return $url;
    }
}
