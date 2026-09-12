define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select'
], function ($, _, uiRegistry, select) {
    'use strict';
    return select.extend({
                initialize: function (){
             this._super();

            var value = this._super().initialValue;
             setTimeout(function () {
                var faqgroup = uiRegistry.get('index = group_faq');
                var customeremail = uiRegistry.get('index = email_faq');
                console.log(faqgroup);
                 var currentURL = window.location.href;
                 if (currentURL.indexOf('dolphin_productfaq/productfaq/new') !== -1) {
                     $('div[data-index="assign_products"]').show();
                     $("[data-index='email_faq']").hide();
                 }else {
                     if (faqgroup.initialValue == 0) {
                         console.log("products_hide");
                         $('div[data-index="assign_products"]').hide();
                         $("[data-index='email_faq']").show();
                     } else {
                         console.log("products_show");
                         $('div[data-index="assign_products"]').show();
                         $("[data-index='email_faq']").hide();
                     }
                 }
            }, 1500);
            return this;
        },

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {

            this.fieldDepend(value);
            return this._super();
        },

        /**
         * Update field dependency
         *
         * @param {String} value
         */
        fieldDepend: function (value) {
            setTimeout(function () {
                var currentURL = window.location.href;
                if (currentURL.indexOf('dolphin_productfaq/productfaq/new') !== -1) {
                    $('div[data-index="assign_products"]').show();
                    $("[data-index='email_faq']").hide();
                }else {
                    var faqgroup = uiRegistry.get('index = group_faq');
                    if (value == 0) {
                        console.log("field_hide");
                        $('div[data-index="assign_products"]').hide();
                        $("[data-index='email_faq']").show();
                    } else {
                        console.log("field_show");
                        $('div[data-index="assign_products"]').show();
                        $("[data-index='email_faq']").hide();
                    }
                }
            }, 1);
            return this;
        }
    });
});
