/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    'jquery'
], function ($, _) {
    "use strict";

    $.widget('mageside.recipeSearch', {

        _create: function () {
            this.initSearch();
            this._bind();
        },

        _bind: function () {
            var self = this;
            this.element.on('submit', function (event) {
                event.preventDefault();
                var query = $(event.currentTarget).find('input[name=search]').val();
                if (query || self.options.currentQuery) {
                    self.options.currentQuery = query;
                    $('body').trigger('recipe.searchChanged', {'search': query});
                }
            });
        },

        initSearch: function () {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) {
                this.options.currentQuery = urlParams.get('search');
                this.element.find('input[name=search]').val(this.options.currentQuery);
            }
        }
    });

    return $.mageside.recipeSearch;
});
