/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    'jquery',
    'underscore'
], function ($, _) {
    "use strict";

    $.widget('mageside.loadRecipeList', {

        options: {
            url: '',
            originalUrl: '',
            method: 'GET',
            recipeMoreBlock: '.recipe-grid',
            currentPage: 1,
            countPage: 0,
            ajaxEnabled: false,
            triggersEnabled: true,
            params: {
                writer: '',
                productId: '',
                filters: {},
                search: '',
                page: 1
            },
            hideParams: []
        },

        _create: function () {
            this.options.originalUrl = window.location.href;

            this._bind();

            if (this.options.countPage > 0 && this.options.ajaxEnabled) {
                this.options.params.page = 1;
                this.load(this.options.params);
            } else {
                this.updateButtonMoreRecipe();
                this.updateTitles();
            }
        },

        _bind: function () {
            if (!this.options.triggersEnabled) {
                return;
            }

            var self = this;

            $('.update-recipes', this.element).on('click', function (event) {
                event.preventDefault();
                self.options.params.page += 1;
                self.load(self.options.params);
            });

            $('body')
                .on('recipe.searchChanged', function (event, data) {
                    self.options.params.page = 1;
                    self.options.params.search = data.search;
                    self.load(self.options.params);
                })
                .on('recipe.filtersChanged', function (event, data) {
                    self.options.params.page = 1;
                    self.options.params.filters = data;
                    self.load(self.options.params);
                });
        },

        load: function (params) {
            var self = this,
                url = this.prepareUrl(self.options.url, params);

            $.ajax({
                showLoader: true,
                url: url,
                type: self.options.method,
                dataType: 'json',
                beforeSend: function () {
                    $('body').trigger('processStart');
                },
                success: function (response) {
                    self.options.countPage = response.countPage || 0;
                    if (response.recipes) {
                        self.options.currentPage = self.options.params.page;
                        self.updateContent(response.recipes);
                        self.updateUrl(params);
                    }
                }
            }).always(function () {
                $('body').trigger('processStop');
            });
        },

        updateContent: function (content) {
            if (this.options.currentPage === 1) {
                $(this.options.recipeMoreBlock, this.element).html(content);
            } else {
                $(this.options.recipeMoreBlock, this.element).append(content);
            }

            this.updateButtonMoreRecipe();
            this.updateTitles();
        },

        updateButtonMoreRecipe: function () {
            if (this.options.countPage > this.options.currentPage) {
                $('.more-recipes', this.element).addClass('active').show();
            } else {
                $('.more-recipes', this.element).removeClass('active').hide();
            }
        },

        updateTitles: function () {
            var items = [],
                maxHeight = 0;

            $('.recipe-grid > li', this.element).each(function () {
                var title = $(this).find('.recipe-item-details');
                items.push(title.get(0));
                maxHeight = Math.max(title.outerHeight(true), maxHeight);
            });

            if (items.length) {
                $(items).height(maxHeight);
            }
        },

        updateUrl: function (params) {
            params = Object.assign({}, params);
            _.each(this.options.hideParams,function (item) {
                if (params.hasOwnProperty(item)) {
                    delete params[item];
                }
            });

            var url = this.prepareUrl(this.options.originalUrl, params);
            if (history.pushState) {
                window.history.pushState({path: url},'', url);
            }
        },

        prepareUrl: function (url, params) {
            url = new URL(url);
            this.preparePath(params, url.searchParams);

            return url.toString();
        },

        preparePath: function (params, searchParams) {
            var self = this;
            searchParams = searchParams || (new URLSearchParams());

            _.each(params,function (value, key) {
                if (_.isObject(value)) {
                    if (_.isEmpty(value)) {
                        return;
                    }
                    self.preparePath(value, searchParams);
                } else {
                    if (key === 'page' && value === 1) {
                        searchParams.delete(key);
                        return;
                    }
                    value = value + ''; // convert to string
                    if (_.isUndefined(value) || _.isNull(value) || value.trim().length === 0) {
                        searchParams.delete(key);
                        return;
                    }
                    if (searchParams.has(key)) {
                        searchParams.set(key, value);
                    } else {
                        searchParams.append(key, value);
                    }
                }
            });

            return searchParams;
        }
    });

    return $.mageside.loadRecipeList;
});
