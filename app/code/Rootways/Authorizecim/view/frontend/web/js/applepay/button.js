/**
 * Rootways Authorize.net Apple Pay button
 * @author Rootways Developer <developer@rootways.com>
 */
define([
    'uiComponent',
    "knockout",
    "jquery",
    'mage/translate',
    'mage/url',
    'mage/storage',
    'Magento_Checkout/js/model/quote'
], function (
    Component,
    ko,
    jQuery,
    $t,
    url,
    storage,
    quote
) {
    'use strict';

    var that;

    return {
        init: function (element, context) {
            if (this.allowApplePay() === false) {
                return;
            }
            var el = document.createElement('div');
            el.className = "rwauthorizecim-apple-pay-button";
            el.title = $t("Pay with Apple Pay");
            el.alt = $t("Pay with Apple Pay");
            var self = this;
            el.addEventListener('click', function (e) {
                e.preventDefault();
                self.applePayButtonClicked(context);
            });
            element.appendChild(el);
        },
        
        /*
        productApplePayButtonClicked: function(context, el) {
            var self = this,
                request = [],
                form = jQuery('#product_addtocart_form'),
                shipping_id = null;
            
            request = form.serialize();
            var serviceUrl = url.build('rest/V1/rwauthorize/apis/addtocart', {}),
                payload = {request: request, shipping_id: shipping_id};
            
            return storage.post(
                serviceUrl,
                JSON.stringify(payload),
                false
            ).fail(function (xhr, textStatus, errorThrown) {
                var response = JSON.parse(xhr.responseText);
                callback(response.message, response);
            }).done(function (response) {
                //self.processResponseWithPaymentIntent(response, callback);
                var responseParse = JSON.parse(response);
                console.log('Response ID = '+responseParse.quoteid);
                console.log('Response Total = '+responseParse.total);
                context.setDisplayName('TEST PAY');
                context.setGrandTotalAmount(responseParse.total);
                context.setQuoteId(responseParse.quoteid);
                el.addEventListener("click", function(e){
                    //e.preventDefault();
                    self.applePayButtonClicked(context); 
                });
            });
        },
        
        addToCart: function(request, shipping_id, callback)
        {
            var serviceUrl = url.build('rest/V1/rwauthorize/apis/addtocart', {}),
                payload = {request: request, shipping_id: shipping_id},
                self = this;

            return storage.post(
                serviceUrl,
                JSON.stringify(payload),
                false
            ).fail(function (xhr, textStatus, errorThrown) {
                var response = JSON.parse(xhr.responseText);
                callback(response.message, response);
            }).done(function (response) {
                //self.processResponseWithPaymentIntent(response, callback);
            });
        },
        */
        
        applePayButtonClicked: function(context) {
            
            //!quote.isVirtual()
            
            
            /*console.log('Button Clicked');
            // Below code is for generate order from product detail page quick pay button.
            var validateUrl = url.build('rootways_authorizecim/applepay/index');
            try {
                var session = new ApplePaySession(1, context.getApplePayRequest());
            } catch (err) {
                jQuery("body").loader('hide');
                console.error('Rootways Authorize.net ApplePay Unable to create ApplePaySession', err);
                alert($t("We're unable to take payments through Apple Pay at the moment. Please try an alternative payment method."));
                return false;
            }
            
            var self = this,
                request = [],
                form = jQuery('#product_addtocart_form'),
                shipping_id = null;
            
            request = form.serialize();
            var serviceUrl = url.build('rest/V1/rwauthorize/apis/addtocart', {}),
                payload = {request: request, shipping_id: shipping_id};
            
            return storage.post(
                serviceUrl,
                JSON.stringify(payload),
                false
            ).fail(function (xhr, textStatus, errorThrown) {
                var response = JSON.parse(xhr.responseText);
                callback(response.message, response);
            }).done(function (response) {
                //self.processResponseWithPaymentIntent(response, callback);
                var responseParse = JSON.parse(response);
                console.log('Response ID = '+responseParse.quoteid);
                console.log('Response Total = '+responseParse.total);
                context.setDisplayName('TEST PAY');
                context.setGrandTotalAmount(responseParse.total);
                context.setQuoteId(responseParse.quoteid);
                //self.applePayButtonClicked(context);
                session.begin();
            });
            */
            
            
            
            
            var validateUrl = url.build('rootways_authorizecim/applepay/index');
            try {
                var session = new ApplePaySession(1, context.getApplePayRequest());
            } catch (err) {
                jQuery("body").loader('hide');
                console.error('Rootways Authorize.net ApplePay Unable to create ApplePaySession', err);
                alert($t("We're unable to take payments through Apple Pay at the moment. Please try an alternative payment method."));
                return false;
            }
            
            var merchantValidationFn = function (valURL) {
                return new Promise(function(resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.onload = function() {
                        var data = JSON.parse(this.responseText);
                        resolve(data);
                    };
                    xhr.onerror = reject;
                    xhr.open('GET', validateUrl, true);
                    xhr.send('validationUrl='+valURL);
                });
            };
            // Merchant Validation
            session.onvalidatemerchant = function (event) {
                var promise = merchantValidationFn(event.validationURL);
                promise.then(function (merchantSession) {
                    session.completeMerchantValidation(merchantSession);
                }); 
            };

            /*
            session.onpaymentmethodselected = function(event) {
                var newTotal = { type: 'final', label: 'Test Spices', amount: gAmt };
                var newLineItems =[{type: 'final',label: 'Spice #202', amount: gAmt }];
                session.completePaymentMethodSelection( newTotal, newLineItems);
            };
            */

            session.onpaymentauthorized = function (event) {
                var dataObj = event.payment.token.paymentData;
                let objJsonStr = JSON.stringify(dataObj);
                let objJsonB64 = window.btoa(objJsonStr);

                if (objJsonB64){
                    status = ApplePaySession.STATUS_SUCCESS;
                } else {
                    status = ApplePaySession.STATUS_FAILURE;
                }
                jQuery("body").loader('hide');
                context.startPlaceOrder(objJsonB64, event, session);
            };

            // Attach onShippingContactSelect method
            if (typeof context.onShippingContactSelect === 'function') {
                session.onshippingcontactselected = function (event) {
                    return context.onShippingContactSelect(event, session);
                };
            }

            // Attach onShippingMethodSelect method
            if (typeof context.onShippingMethodSelect === 'function') {
                session.onshippingmethodselected = function (event) {
                    return context.onShippingMethodSelect(event, session);
                };
            }

            session.oncancel = function(event) {
                console.log(event);
            };

            session.begin();
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
        }
    };
});
