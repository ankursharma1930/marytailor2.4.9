/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    "jquery",
    "underscore",
    "Magento_Ui/js/form/form"
], function ($, _, Form) {
    'use strict';

    return Form.extend({
        setAdditionalData: function (data) {
            var rating = $('[data-widget="ratingControl"] input[type="radio"]:checked').val();
            var name = $('[data-widget="ratingControl"] input[type="radio"]:checked').attr("name");
            data[name] = rating;

            _.each(data, function (rating, name) {
                this.source.set('data.' + name, rating);
            }, this);

            return this;
        }
    })
});
