/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    'jquery',
    'underscore'
], function ($, _) {
    "use strict";

    $.widget('mageside.recipeFilters', {
        options: {
            triggerEvent: 'change'
        },

        _create: function () {
            this.initFiltersSelected();
            this._bind();
        },

        _bind: function () {
            var self = this;
            self.element.find('select').on(self.options.triggerEvent, function () {
                self.triggerFiltersChanged();
            });
        },

        triggerFiltersChanged: function () {
            var self = this,
                data = {};

            _.each(self.element.find('select'), function (element) {
                if (element.value) {
                    data[element.name] = element.value;
                }
            });

            $('body').trigger('recipe.filtersChanged', data);
        },

        initFiltersSelected: function () {
            var urlParams = new URLSearchParams(window.location.search);
            _.each($('select', this.element), function (element) {
                if (urlParams.has(element.name)) {
                    $(element).val(urlParams.get(element.name));
                }
            });
        }
    });

    return $.mageside.recipeFilters;
});
