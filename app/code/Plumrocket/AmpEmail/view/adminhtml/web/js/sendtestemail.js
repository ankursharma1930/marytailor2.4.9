/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

/**
 * @since 1.0.1
 */
require([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'domReady!'
], function ($, alert) {
    "use strict";

    window.sendPrAmpTestEmail = function (url) {
        $.ajax({
            url: url,
            data: {to: $('#prampemail_test_email_to').val()},
            type: 'POST',
            cache: true,
            dataType: 'json',
            showLoader: true,

            /**
             * Response handler
             * @param {Object} data
             */
            success: function (data) {
                alert({
                    title: $.mage.__('Send test email'),
                    content: data.message,
                });
            },
            error: function () {
                alert({
                    title: $.mage.__('Send test email'),
                    content: $.mage.__('Something went wrong. Please review the log for details or try later.'),
                });
            }
        });
    };
});
