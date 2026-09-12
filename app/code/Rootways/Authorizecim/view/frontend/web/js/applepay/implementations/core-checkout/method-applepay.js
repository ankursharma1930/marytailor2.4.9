define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'rootways_authorizecim_option_applepay',
            component: 'Rootways_Authorizecim/js/applepay/implementations/core-checkout/method-renderer/applepay'
        }
    );

    return Component.extend({});
});
