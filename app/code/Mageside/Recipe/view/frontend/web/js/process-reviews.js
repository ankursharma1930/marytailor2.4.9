/**
 * Copyright © Mageside, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mageside.loadRecipeReview', {

        _create: function() {
            this.bind();
        },

        bind: function() {
            var self = this;
            self.element.find('.pages a').each(function (index, element) {
                $(element).click(function (event) {
                    self.processReviews($(element).attr('href'));
                    event.preventDefault();
                });
            });
        },

        processReviews: function (url) {
            var self = this;
            $.ajax({
                url: url,
                cache: true,
                dataType: 'html',
                beforeSend: function () {
                    $('body').trigger('processStart');
                }
            }).done(function (data) {
                self.element.html(data);
                self.bind();
            }).complete(function () {
                $('body').trigger('processStop');
                $('html, body').animate({
                    scrollTop: $('#reviews').offset().top - 50
                }, 300);
            });
        }
    });

    return $.mageside.loadRecipeReview;
});
