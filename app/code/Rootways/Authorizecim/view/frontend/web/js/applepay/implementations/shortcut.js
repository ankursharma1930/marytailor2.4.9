define([
    'uiComponent',
    'Rootways_Authorizecim/js/applepay/button',
    'Rootways_Authorizecim/js/applepay/api',
    'mage/translate',
    'domReady!'
], function (
    Component,
    button,
    buttonApi,
    $t
) {
    'use strict';

    return Component.extend({

        defaults: {
            id: null,
            quoteId: 0,
            displayName: null,
            grandTotalAmount: 0,
            isLoggedIn: false,
            storeCode: "default",
            merchantCountryCode: "US",
            currencyCode: "USD"
        },

        /**
         * @returns {Object}
         */
        initialize: function () {
            this._super();
            if (!this.displayName) {
                this.displayName = $t('Store');
            }

            var api = new buttonApi();
            api.setGrandTotalAmount(parseFloat(this.grandTotalAmount).toFixed(2));
            api.setDisplayName(this.displayName);
            api.setQuoteId(this.quoteId);
            api.setIsLoggedIn(this.isLoggedIn);
            api.setStoreCode(this.storeCode);
            api.setCurrencyCode(this.currencyCode);
            api.setMerchantCountryCode(this.merchantCountryCode);

            // Attach the button
            button.init(
                document.getElementById(this.id),
                api
            );

            return this;
        }
    });
});
