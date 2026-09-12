/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'mage/validation'
], function ($, modal, $t) {
    "use strict";

    $.widget('mageside.addAllToCart', {

        options: {
            reviewUrl: null,
            url: null,
            reviewIngredientsModalSelector: '',
            reviewFormSelector: '.review-ingredients-form',
            reviewPopup: null
        },

        _create: function () {
            this._bind();
        },

        _bind: function () {
            var self = this;
            self.element.on('click', function () {
                self.review();
            })
        },

        review: function () {
            var self = this;
            this.options.reviewPopup = $(this.options.reviewIngredientsModalSelector);
            var options = {
                'type': 'popup',
                'modalClass': 'review-ingredients-modal',
                'title': $t('Review Ingredients'),
                'responsive': true,
                'innerScroll': true,
                'trigger': '.show-modal',
                'buttons': [
                    {
                        text: $t('Close'),
                        class: 'action secondary action-hide-popup',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: $t('Add to Cart'),
                        class: 'action secondary action-hide-popup',
                        click: function () {
                            self.addToCart(this.closeModal);
                        }
                    }
                ]
            };
            modal(options, this.options.reviewPopup);
            self.loadReviewPopupContent();
        },

        loadReviewPopupContent: function () {
            var self = this;
            $.ajax({
                url: self.options.reviewUrl,
                type: 'post',
                dataType: 'json',
                beforeSend: function () {
                    $('body').trigger('processStart');
                },
                success: function (data) {
                    if (data.ingredients) {
                        $(self.options.reviewIngredientsModalSelector).html(data.ingredients);
                        $(self.options.reviewIngredientsModalSelector).modal('openModal');
                        $('body').trigger('contentUpdated');
                    }
                }
            }).always(function () {
                $('body').trigger('processStop');
            });
        },

        addToCart: function (onSuccessCallback) {
            var self = this,
                $form = $(this.options.reviewFormSelector),
                formKey = $.mage.cookies.get('form_key');

            if ($form.validation() && $form.validation('isValid')) {
                $form.append('<input type="hidden" name="form_key" value="' + formKey + '">');
                var formData = new FormData($form[0]);

                $.ajax({
                    url: $form.attr('action'),
                    type: $form.attr('method'),
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function () {
                        $('body').trigger('processStart');
                    },
                    success: function (data) {
                        if (data['success']) {
                            onSuccessCallback();
                        }
                        if (data['messages']) {
                            $('.messages', self.options.reviewPopup).html(data['messages']);
                        }
                    }
                }).always(function () {
                    $('body').trigger('processStop');
                });
            }
        }
    });

    return $.mageside.addAllToCart;
});
