define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'jquery',
    'Rootways_Authorizecim/js/applepay/button',
    'mage/translate',
    'mage/url',
    'mage/storage',
    'Magento_Checkout/js/model/payment/additional-validators'
], function (
    Component,
    quote,
    jQuery,
    button,
    $t,
    url,
    storage,
    additionalValidators
) {
    'use strict';

    var agreementsConfig = window.checkoutConfig ? window.checkoutConfig.checkoutAgreements : {};
    var agreementsInputPath = '#rootways_authorizecim_option_applepay_wrapper div.checkout-agreements input';

    return Component.extend({
        defaults: {
            template: 'Rootways_Authorizecim/applepay/core-checkout',
            paymentMethodNonce: null,
            grandTotalAmount: 0,
            displayName: null
        },
        agreements: agreementsConfig.agreements,

        getCode: function() {
            return 'rootways_authorizecim_option_applepay';
        },

        allowApplePay: function () {
            if (location.protocol != 'https:') {
                console.warn("Rootways Authorize.net Apple Pay: Apple Pay requires your checkout be served over HTTPS");
                return false;
            }

            if ((window.ApplePaySession && ApplePaySession.canMakePayments()) !== true) {
                console.warn("Rootways Authorize.net ApplePay Apple Pay is not supported on this device/browser");
                return false;
            }

            return true;
        },

        /**
         * Subscribe to grand totals
         */
        initObservable: function () {
            this._super();
            this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
            //this.placeOrderFailure = ko.observable(false);
			//this.placeOrderFailure.subscribe(this.clearToken.bind(this));
            quote.totals.subscribe(function () {
                if (this.grandTotalAmount !== quote.totals()['base_grand_total']) {
                    this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
                }
            }.bind(this));

            this.agreementListner();

            return this;
        },

        agreementValidation: function() {
            if (additionalValidators.validate()) {
                jQuery('.' + this.getCode() + '_iframe_wrapper').removeClass('agreement_missed');
            } else {
                jQuery('.' + this.getCode() + '_iframe_wrapper').addClass('agreement_missed');
            }
        },

        agreementListner: function() {
            var self = this;

            var setAgreeListner = function () {
                if (!jQuery('#' + self.getCode() + '_wrapper .checkout-agreements')[0]) {
                    setTimeout(function(){setAgreeListner();}, 500);
                    return;
                }

                var isValid = true;
                jQuery(agreementsInputPath).each(function (index, element) {
                    element.addEventListener('change', self.agreementValidation.bind(self), false);
                    if (!jQuery.validator.validateSingleElement(element, {
                        errorElement: 'div',
                        hideError: false
                    })) {
                        isValid = false;
                    }
                });
                if (isValid == false) {
                    jQuery('.' + self.getCode() + '_iframe_wrapper').addClass('agreement_missed');
                }
            };
            setAgreeListner();
        },

        /**
         * Apple Pay Session Parameters
         */
        getApplePayRequest: function () {
            var curCode = window.checkoutConfig.payment[this.getCode()].applepayCurrency;
            var conCode = window.checkoutConfig.payment[this.getCode()].applepayMerchantCountryCode;
            return {
                total: {
                    label: this.getDisplayName(),
                    amount: this.grandTotalAmount
                },
                countryCode: conCode,
                currencyCode: curCode,
                supportedNetworks: ['visa', 'masterCard', 'amex', 'discover'],
                merchantCapabilities: ['supports3DS','supportsCredit', 'supportsDebit'] // Make sure NOT to include supportsEMV here
            };
        },

        /**
         * Save nonce
         */
        setPaymentMethodNonce: function (nonce) {
            this.paymentMethodNonce = nonce;
        },

        /**
         * Retrieve the client token
         * @returns null|string
         */
        getClientToken: function () {
            return window.checkoutConfig.payment[this.getCode()].clientToken;
        },

        /**
         * Allowed credit card types for apple pay
         * @returns null|string
         */
        getApplePayCcAvailableCcTypes: function () {
            var availableTypes = window.checkoutConfig.payment[this.getCode()].applePayCcAvailableCcTypes;
            var typearray = jQuery.map(availableTypes, function(value, index) {
                return [index];
            });
            return typearray;
        },

        /**
         * Merchant display name
         */
        getDisplayName: function () {
            return window.checkoutConfig.payment[this.getCode()].merchantName;
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData: function () {
            var data = {
                'method': this.getCode(),
                'additional_data': {
                    'payment_method_nonce': this.paymentMethodNonce
                }
            };
            return data;
        },

        /**
         * Return image url for the apple pay mark
         */
        getPaymentMarkSrc: function () {
            return window.checkoutConfig.payment[this.getCode()].paymentMarkSrc;
        },

        /**
         * Apple Pay button clicked event
         */
        applePayButtonClicked: function() {

            button.applePayButtonClicked(this);
        },

        /**
         * Place Order
         */
        placeOrder: function (data, event) {
			if (this.paymentMethodNonce) {
				return this._super(data, event);
			} else {
				button.applePayButtonClicked(this);
			}

			return false;
		},

        /**
         * Clear Token.
         */
        clearToken: function (placeOrderFailure) {
			if (placeOrderFailure === true) {
				this.paymentMethodNonce(null);
			}
		},

        /*
		getPlaceOrderDeferredObject: function () {
			this.placeOrderFailure(false);

			return this._super()
					   .fail(this.handleFailedOrder.bind(this));
		},

		handleFailedOrder: function (response) {
			this.placeOrderFailure(true);
			this.paymentMethodNonce(null);
			return this._super();

			//var error = JSON.parse(response.responseText);
			//if (error && typeof error.message !== 'undefined') {
			//	alert({
			//		title: $.mage.__('Unable to place order'),
			//		content: error.message
			//	});
			//}
		},
        */

        /**
         * Start palcing order
         */
        startPlaceOrder: function (nonce, event, session) {
            this.setPaymentMethodNonce(nonce);
            this.placeOrder();

            session.completePayment(ApplePaySession.STATUS_SUCCESS);
        }
    });
});
